<?php

namespace App\Resources\Parking2\Methods;


use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Listeners\Resources2\ParallelServiceListener;
use App\Models\Resource;
use App\Resources\Parkandfly\Parking2WrapperAbstractRequest;
use App\Resources\Parking2\Parking;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class UpdatePaymentStatus extends Parking2WrapperAbstractRequest
{
    public function executeFunction()
    {
        $order = $this->getOrderByOrderId($this->params[ResourceInterface::ORDER_ID]);

        if( ! $order){
            $order = $this->getOrderByTransactionId($this->params[ResourceInterface::ORDER_ID]);

            if( ! $order){
                return;
            }

            $this->clearErrors();
        }

        if(($order[ResourceInterface::PAYMENT_STATUS] != ResourceValue::PAYMENT_DEFERRED) && !isset($this->params['skip_payment'])){
            $paymentStatus = $this->internalRequest('payment.multisafepay', 'updateorderpaymentstatus', array_merge($this->params, [
                ResourceInterface::PRODUCT_TYPE => 'parking2',
                ResourceInterface::ORDER_ID     => $order->__id,
            ]), true);

            if($this->resultHasError($paymentStatus)){
                $this->setErrorString('Multisafepay error: ' . json_encode($paymentStatus));
                return;
            }
        }

        $orderId = $order->__id;
        $method  = $this;
        $this->doLockedDocumentTransaction('order', 'parking2', $orderId, function () use ($orderId, $method) {
            $order = $this->getOrderById($orderId);
            if( ! $order){
                return;
            }

            $this->result = array_only($order->toArray(), [
                ResourceInterface::PAYMENT_STATUS,
                ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY,
                ResourceInterface::RESERVATION_STATUS,
                ResourceInterface::RESERVATION_CODE,
            ]);


            //shit is on here...!

            // Notify ParkingCI of the payment status
            $multi = str_contains($order[ResourceInterface::ORDER_ID], '-');
            $orderIds = explode('-', $order[ResourceInterface::ORDER_ID]);

            $paymentNotifyStatus = $order[ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY];
            if ($order[ResourceInterface::PAYMENT_STATUS] == ResourceValue::PAYMENT_DEFERRED)
                $paymentNotifyStatus = 'completed';

            if($multi){
                //multiple orderIds! Time for some parallelism
                $batchInputs = [];
                foreach($orderIds as $orderId){
                    $batchInputs[] = [
                        ResourceInterface::ORDER_ID => $orderId, // Note: not __id
                        ResourceInterface::STATUS   => $paymentNotifyStatus,
                    ];
                }
                $resource = Resource::where('name', 'notify_payment.parkingci')->firstOrFail();
                $notifyPayment = ParallelServiceListener::batch($resource, $batchInputs);
            }else{
                $notifyPayment = $this->internalRequest('parkingci', 'notify_payment', array_merge([
                    ResourceInterface::ORDER_ID => $order[ResourceInterface::ORDER_ID], // Note: not __id
                    ResourceInterface::STATUS   => $paymentNotifyStatus,
                ]), true);
            }


            if($method->resultHasError($notifyPayment, $multi)){
                Log::error('Parking CI notify payment error for order ' . $order[ResourceInterface::ORDER_ID] . ': ' . json_encode($notifyPayment));
            }


            // Fetch reservation data from ParkingCI (Notify may have changed data)
            if ($multi) {
                //multiple orderIds! Time for some parallelism
                $batchInputs = [];
                foreach($orderIds as $orderId){
                    $batchInputs[] = [
                        ResourceInterface::ORDER_ID => $orderId, // Note: not __id
                        ResourceInterface::STATUS   => $order[ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY], // Will get ignored if 'nopay' is configured
                    ];
                }
                $resource = Resource::where('name', 'get_reservation.parkingci')->firstOrFail();
                $reservationData = array_values(ParallelServiceListener::batch($resource, $batchInputs));
                $this->result[ResourceInterface::RESERVATION_CODE] = implode(',',array_pluck($reservationData,ResourceInterface::RESERVATION_CODE));
                cw('completed payments'.count(array_pluck($reservationData,ResourceInterface::PAYMENT_COMPLETE )). ' order ids'.count($orderIds));
            } else{
                $reservationData = $this->internalRequest('parkingci', 'get_reservation', array_merge([
                    ResourceInterface::ORDER_ID => $order[ResourceInterface::ORDER_ID], // Note: not __id
                ]), true);
                $this->result[ResourceInterface::RESERVATION_CODE] = array_get($reservationData, ResourceInterface::RESERVATION_CODE);
            }


            if($method->resultHasError($reservationData, true)){
                $this->result[ResourceInterface::RESERVATION_STATUS] = Parking::RESERVATION_STATUS_ERROR;
            }else if
            (
                (
                    ( !$multi && !$reservationData[ResourceInterface::PAYMENT_COMPLETE]) ||
                    ( $multi && (!(count(array_pluck($reservationData,ResourceInterface::PAYMENT_COMPLETE ))) == count($orderIds)))
                )
                && ($order->payment_status == ResourceValue::PAYMENT_SUCCESS))
            {
                // Not registered as paid while paid? Error!
                Log::error('Parking CI notify payment is not successful for order ' . $order[ResourceInterface::ORDER_ID]);
                $this->result[ResourceInterface::RESERVATION_STATUS] = Parking::RESERVATION_STATUS_ERROR;
            }else{
                // Payment Success or Deferred? Reservation is finalized
                if($order->payment_status == ResourceValue::PAYMENT_SUCCESS || $order->payment_status == ResourceValue::PAYMENT_DEFERRED){

                    if($this->result[ResourceInterface::RESERVATION_CODE] === 'FALSE'){
                        $this->result[ResourceInterface::RESERVATION_STATUS] = Parking::RESERVATION_STATUS_ERROR;
                    }else{
                        $this->result[ResourceInterface::RESERVATION_STATUS] = Parking::RESERVATION_STATUS_COMPLETED;
                    }
                }

                // Payment Failure and was never a Success before? Abandoned reservation.
                if($order->payment_status == ResourceValue::PAYMENT_FAILED){
                    $this->result[ResourceInterface::RESERVATION_STATUS] = Parking::RESERVATION_STATUS_CANCELED;
                    // Todo : Cancel Reservation?
                }
            }

            DocumentHelper::update('order', 'parking2', $order->__id, [
                ResourceInterface::RESERVATION_STATUS => $this->result[ResourceInterface::RESERVATION_STATUS],
                ResourceInterface::RESERVATION_CODE   => $this->result[ResourceInterface::RESERVATION_CODE],
                ResourceInterface::RESERVATION_RESULT => $reservationData,
            ]);
        });
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
}