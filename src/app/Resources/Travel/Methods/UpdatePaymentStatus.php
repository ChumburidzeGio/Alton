<?php

namespace App\Resources\Travel\Methods;


use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Listeners\Resources2\ParallelServiceListener;
use App\Models\Resource;
use App\Resources\Travel\Travel;
use App\Resources\Travel\TravelWrapperAbstractRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Komparu\Document\Document;

class UpdatePaymentStatus extends TravelWrapperAbstractRequest
{

    protected function getOrderByOrderIdOrTransactionId($orderIdOrTransactionId)
    {
        $order = $this->getOrderByOrderId($orderIdOrTransactionId);

        if (!$order){
            $order = $this->getOrderByTransactionId($orderIdOrTransactionId);

            if (!$order){
                return null;
            }

            $this->clearErrors();
        }

        return $order;
    }

    public function executeFunction()
    {
        $order = $this->getOrderByOrderIdOrTransactionId($this->params[ResourceInterface::ORDER_ID]);

        if (!$order || $this->hasErrors())
            return;

        $previousPaymentStatus = $order[ResourceInterface::PAYMENT_STATUS];

        // Update payment_status by calling our generic MultiSafepay payment status update method.
        if(($previousPaymentStatus !== ResourceValue::PAYMENT_DEFERRED) && ! isset($this->params[ResourceInterface::SKIP_PAYMENT])){
            $paymentStatus = $this->internalRequest('payment.multisafepay', 'updateorderpaymentstatus', array_merge($this->params, [
                ResourceInterface::PRODUCT_TYPE => 'travel',
                ResourceInterface::ORDER_ID     => $order->__id,
            ]), true);

            if($this->resultHasError($paymentStatus)){
                $this->setErrorString('Multisafepay error: ' . json_encode($paymentStatus));
                return;
            }
        }

        $orderId = $order->__id;
        $method  = $this;
        // Wrap in transation so we do not have race conditions
        $this->doLockedDocumentTransaction('order', 'travel', $orderId, function () use ($orderId, $method, $previousPaymentStatus) {
            $order = $this->getOrderById($orderId);
            if (!$order) {
                return;
            }

            $startStatus = $order[ResourceInterface::RESERVATION_STATUS];

            if ($order[ResourceInterface::USE_DIRECT_RESERVATION]) {
                $order = $method->processDirectPaymentUpdate($order);
            }
            else {
                $order = $method->processParkingCiPaymentUpdate($order);
            }

            $this->result = array_only($order->toArray(), [
                ResourceInterface::PAYMENT_STATUS,
                ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY,
                ResourceInterface::RESERVATION_STATUS,
                ResourceInterface::RESERVATION_CODE,
            ]);

            // Fire email event after order is completed, and it wasn't before.
            if ($order[ResourceInterface::RESERVATION_STATUS] == Travel::RESERVATION_STATUS_COMPLETED
                && $startStatus != Travel::RESERVATION_STATUS_COMPLETED)
            {
                Event::fire('email.notify', ['travel', 'reservation.complete', $orderId, $order->website]);
            }
        });
    }

    protected function processDirectPaymentUpdate($order)
    {
        // Remote order was already created
        if (!empty($order[ResourceInterface::REMOTE_ORDER_ID]))
            return $order;

        $newOrderData = [];

        // Huzzah, payment is 'done'
        if (in_array($order[ResourceInterface::PAYMENT_STATUS], [ResourceValue::PAYMENT_SUCCESS, ResourceValue::PAYMENT_DEFERRED])) {
            if ($order[ResourceInterface::IS_TEST_ORDER]) {
                // If test, we 'fake set' to completed
                $newOrderData = [
                    ResourceInterface::RESERVATION_STATUS => Travel::RESERVATION_STATUS_COMPLETED,
                ];
            }
            else {
                // Create order(s) in remote APIs
                // (Multiple orders may be created because of multiple cars per order)
                $remoteOrders = $this->createRemoteOrders($order);

                $newOrderData = [
                    ResourceInterface::RESERVATION_RESULT => json_encode($remoteOrders),
                    ResourceInterface::REMOTE_ORDER_ID => implode(',', array_fetch($remoteOrders, ResourceInterface::ORDER_ID)),
                    ResourceInterface::RESERVATION_CODE => implode(',', array_fetch($remoteOrders, ResourceInterface::RESERVATION_CODE)),
                    ResourceInterface::RESERVATION_STATUS => $this->hasErrors() || count($remoteOrders) == 0 ? Travel::RESERVATION_STATUS_ERROR : Travel::RESERVATION_STATUS_COMPLETED,
                ];
            }
        }
        // Payment failed...
        if ($order[ResourceInterface::PAYMENT_STATUS] == ResourceValue::PAYMENT_FAILED && $order[ResourceInterface::RESERVATION_STATUS] != Travel::RESERVATION_STATUS_COMPLETED) {
            // Payment Failure and was never a Success before? Abandoned reservation.
            // Todo: Make this 'RESERVATION_STATUS_VOID' or something?
            $newOrderData = [
                ResourceInterface::RESERVATION_STATUS => Travel::RESERVATION_STATUS_CANCELED,
            ];
        }

        if ($newOrderData) {
            DocumentHelper::update('order', 'travel', $order->__id, $newOrderData);
            $order = self::getOrderByOrderId($order->order_id);
        }

        return $order;
    }

    /**
     * Note: this method can be removed once the Parking CodeIgniter is removed when USE_DIRECT_RESERVATION is on for every environment.
     */
    protected function processParkingCiPaymentUpdate($order)
    {
        // Notify ParkingCI of the payment status.
        // NOTE: ParkingCI will do any external API reservation creation! (by calling create_reservation.*)
        $this->notifyParkingCiPaymentStatus($order);

        // Fetch reservation data from ParkingCI (Notify may have changed data)
        $parkingCiOrders = $this->getParkingCiOrders($order);
        $newOrderData = $this->getStatusFromParkingCiOrders($order, $parkingCiOrders);

        // Payment Failure and was never a Success before? Abandoned reservation.
        if ($order->payment_status == ResourceValue::PAYMENT_FAILED && $newOrderData[ResourceInterface::RESERVATION_STATUS] != Travel::RESERVATION_STATUS_COMPLETED) {
            $newOrderData[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_CANCELED;
        }

        DocumentHelper::update('order', 'travel', $order->__id, $newOrderData);

        return self::getOrderByOrderId($order->order_id);
    }

    /**
     * TODO: move this lock/unlock to Komparu/Document:lock/unlock
     */
    protected function doLockedDocumentTransaction($documentIndex, $documentType, $documentId, callable $callback)
    {
        /** @var \PDO $pdo */
        $pdo = App::Make(\FluentPDO::class)->getPDO();
        $pdo->beginTransaction();
        $query = $pdo->prepare('SELECT * FROM `' . str_replace('`', '', $documentIndex . '_' . $documentType) . '` WHERE __id = :id FOR UPDATE');

        if( ! $query){
            $this->setErrorString('Could not obtain lock on ' . $documentIndex . '_' . $documentType . ' - ' . $documentId . ':' . json_encode($pdo->errorInfo()));
            return;
        }

        $query->execute(['id' => $documentId]);

        try{
            $callback();
        }finally{
            App::Make(\FluentPDO::class)->getPDO()->commit();
        }
    }

    protected function createRemoteOrders(Document $order)
    {
        // Construct all orders
        $remoteOrders = self::getRemoteOrdersParams($order->toArray());

        $resourceType = self::getRemoteResourceType($order[ResourceInterface::PRODUCT_ID]);

        // Attempt to create all orders
        $orderResults = [];
        $failedOrder  = false;
        foreach($remoteOrders as $remoteOrder){
            try {
                $orderResults[] = ResourceHelper::callResource2('create_reservation.' . $resourceType, $remoteOrder);
            }
            catch (\Exception $e) {
                $failedOrder = true;
                $orderResults[]['error'] = (string)$e;
            }
        }

        // Cancel all orders if one failed
        if($failedOrder){
            foreach($orderResults as $orderResult){
                if(isset($orderResult[ResourceInterface::ORDER_ID])){
                    try {
                        ResourceHelper::callResource2('cancel_reservation.' . $resourceType, [ResourceInterface::ORDER_ID => $orderResult[ResourceInterface::ORDER_ID]]);
                    }
                    catch (\Exception $e) {
                        Log::error('Could not cancel Remote order #' . $orderResult[ResourceInterface::ORDER_ID] . ' - ' . $e->getMessage());
                    }
                }
            }
            $this->addErrorMessage(ResourceInterface::DEPARTURE_DATE, 'generic-error', 'Could not create remote order.');
        }

        // In case of ParkingCI, poke notify to finish order(s)
        // Note: This if statement can be removed when Parking CI is no longer in use
        if (!$failedOrder && $resourceType == 'parkingci') {
            if (count($orderResults) > 1)
            {
                $batchInputs = [];
                foreach($orderResults as $orderResult){
                    $batchInputs[] = [
                        ResourceInterface::ORDER_ID => $orderResult[ResourceInterface::ORDER_ID], // Note: not __id
                        ResourceInterface::STATUS   => 'completed',
                    ];
                }
                $resource      = Resource::where('name', 'notify_payment.parkingci')->firstOrFail();
                $notifyPayment = ParallelServiceListener::batch($resource, $batchInputs);
            }
            else
            {
                $notifyPayment = $this->internalRequest('parkingci', 'notify_payment', array_merge([
                    ResourceInterface::ORDER_ID => head($orderResults)[ResourceInterface::ORDER_ID],
                    ResourceInterface::STATUS   => 'completed',
                ]), true);
            }

            // Check for notify errors (notify may still succeed, even if reservation in notify fails)
            if($this->resultHasError($notifyPayment, count($orderResults) > 1)){
                Log::error('Parking CI notify payment error for order ' . $order[ResourceInterface::ORDER_ID] . ': ' . json_encode($notifyPayment));
                $this->setErrorString('Parking CI notify payment error for order ' . $order[ResourceInterface::ORDER_ID] . ': ' . json_encode($notifyPayment));
            }
        }

        return $orderResults;
    }


    /**
     * Note: this method can be removed once the Parking CodeIgniter is removed when USE_DIRECT_RESERVATION is on for every environment.
     */
    protected function notifyParkingCiPaymentStatus($order)
    {
        if ($order[ResourceInterface::PAYMENT_STATUS] == ResourceValue::PAYMENT_DEFERRED)
            $paymentNotifyStatus = 'completed';
        else
            $paymentNotifyStatus = $order[ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY];

        $parkingCiOrderIds = $this->getParkingCiOrderIdsFromOrder($order);

        // Call ParkingCI for each order
        if (count($parkingCiOrderIds) > 1)
        {
            $batchInputs = [];
            foreach($parkingCiOrderIds as $orderId){
                $batchInputs[] = [
                    ResourceInterface::ORDER_ID => $orderId, // Note: not __id
                    ResourceInterface::STATUS   => $paymentNotifyStatus,
                    ResourceInterface::TRANSACTION_COSTS => $order[ResourceInterface::TRANSACTION_COSTS] / count($parkingCiOrderIds),
                ];
            }
            $resource      = Resource::where('name', 'notify_payment.parkingci')->firstOrFail();
            $notifyPayment = ParallelServiceListener::batch($resource, $batchInputs);
        }
        else
        {
            $notifyPayment = $this->internalRequest('parkingci', 'notify_payment', array_merge([
                ResourceInterface::ORDER_ID => head($parkingCiOrderIds),
                ResourceInterface::STATUS   => $paymentNotifyStatus,
                ResourceInterface::TRANSACTION_COSTS  => $order[ResourceInterface::TRANSACTION_COSTS],
            ]), true);
        }


        // Check for notify errors (notify may still succeed, even if reservation in notify fails)
        if($this->resultHasError($notifyPayment, count($parkingCiOrderIds) > 1)){
            Log::error('Parking CI notify payment error for order ' . $order[ResourceInterface::ORDER_ID] . ': ' . json_encode($notifyPayment));
        }
    }

    /**
     * Note: this method can be removed once the Parking CodeIgniter is removed when USE_DIRECT_RESERVATION is on for every environment.
     */
    public function getStatusFromParkingCiOrders($order, $parkingCiOrders)
    {
        $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_PENDING;

        if (is_string($order->product))
            $order->product = json_decode($order->product, true);

        foreach ($parkingCiOrders as $parkingCiOrder) {
            if ($this->resultHasError($parkingCiOrder)) {
                $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_ERROR;
                break;
            }

            switch ($parkingCiOrder[ResourceInterface::STATUS]) {
                case 'NEW':
                case 'UPDATE':
                    if (array_get($order->product, 'resource.name') == 'prices.parkingci' || !array_get($order->product, 'resource.name')) {
                        // Successfully created order
                        $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_COMPLETED;
                    }
                    else {
                        // When not parkingci, we should always have a status like 'RESERVATION-*' for a result
                        $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_PENDING;
                    }
                    break;
                case 'RESERVATION-SUCCESS':
                case 'UPDATE-RESERVATION-SUCCESS':
                    // Successfully created order with external reservation

                    // potentially still without a reservation code: error
                    if (empty($parkingCiOrder[ResourceInterface::RESERVATION_CODE]) || $parkingCiOrder[ResourceInterface::RESERVATION_CODE] == 'FALSE') {
                        $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_ERROR;
                        break 2;
                    }

                    $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_COMPLETED;
                    break;
                case 'RESERVATION-FAILED':
                case 'UPDATE-RESERVATION-FAILED':
                    $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_ERROR;
                    break 2;
                case 'CANCEL':
                    $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_CANCELED;
                    break 2;
                default:
                    // Multisafepay statusses
                    if ($parkingCiOrder[ResourceInterface::STATUS] != 'msp-'. $order[ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY])
                        Log::error('Parking CI payment status does not match local payment status.');

                    if ($parkingCiOrder[ResourceInterface::STATUS] == 'msp-completed')
                        $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_COMPLETED;
                    else if ($parkingCiOrder[ResourceInterface::STATUS] == 'msp-error')
                        $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_ERROR;
                    else
                        $newOrderState[ResourceInterface::RESERVATION_STATUS] = Travel::RESERVATION_STATUS_PENDING;
                    break;
            }
        }

        if ($newOrderState[ResourceInterface::RESERVATION_STATUS] == Travel::RESERVATION_STATUS_COMPLETED)
            $newOrderState[ResourceInterface::RESERVATION_CODE] = implode(',', array_filter(array_pluck($parkingCiOrders, ResourceInterface::RESERVATION_CODE)));

        return $newOrderState;
    }

    /**
     * Note: this method can be removed once the Parking CodeIgniter is removed when USE_DIRECT_RESERVATION is on for every environment.
     *
     * @param $order
     */
    protected function getParkingCiOrders($order)
    {
        if (preg_match('/^taxi_/', $order[ResourceInterface::ORDER_ID]))
            return [];

        $parkingCiOrderIds = self::getParkingCiOrderIdsFromOrder($order);

        $batchInputs = [];
        foreach ($parkingCiOrderIds as $orderId) {
            $batchInputs[] = [
                ResourceInterface::ORDER_ID => $orderId, // Note: not __id
            ];
        }
        $resource = Resource::where('name', 'get_reservation.parkingci')->firstOrFail();
        return array_values(ParallelServiceListener::batch($resource, $batchInputs));
    }

}