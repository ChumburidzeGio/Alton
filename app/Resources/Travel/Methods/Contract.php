<?php


namespace App\Resources\Travel\Methods;

use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Listeners\Resources2\TravelListener;
use App\Models\User;
use App\Models\Website;
use App\Resources\Travel\Travel;
use App\Resources\Travel\TravelWrapperAbstractRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Komparu\Document\Contract\Document;

/**
 * @property array product
 */
class Contract extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        // Hacky naming mapping
        if(isset($this->params[ResourceInterface::AVAILABLE_OPTIONS])){
            $this->params[ResourceInterface::OPTIONS] = $this->params[ResourceInterface::AVAILABLE_OPTIONS];
        }

        $order   = self::getOrderById(array_get($this->params, '_forinternaluse.' . ResourceInterface::ORDER_ID));

        $skipPayment = (bool)$this->getRightSetting(ResourceInterface::SKIP_PAYMENT, array_get($this->params, ResourceInterface::USER), array_get($this->params, ResourceInterface::WEBSITE));

        if (((app()->configure('resource_travel')) ? '' : config('resource_travel.use_direct_reservation'))) {
            // Permanent new order ID
            $newOrderId = $this->generateRandomUniqueOrderId();
        } else {
            // Temporary OrderID, in case we never get one from ParkingCI
            $newOrderId = 'tem_' . rand(10000001, 99999999);
        }

        $website = Website::find(array_get($this->params, ResourceInterface::WEBSITE));
        if (!$website || $website->user_id != array_get($this->params, ResourceInterface::USER))
            $this->addErrorMessage('website', 'travel.error.website_invalid', 'Website ID does not exist or is invalid.');

        $orderData = [
            // Identifying data
            ResourceInterface::WEBSITE                     => array_get($this->params, ResourceInterface::WEBSITE),
            ResourceInterface::USER                        => array_get($this->params, ResourceInterface::USER),
            ResourceInterface::ORDER_ID                    => $newOrderId,
            ResourceInterface::AMOUNT                      => array_get($this->params, ResourceInterface::PRICE),

            // Payment details
            ResourceInterface::PAYMENT_AMOUNT_PAID         => 0,
            ResourceInterface::CURRENCY                    => 'EUR',

            // Personal details
            ResourceInterface::FIRST_NAME                  => array_get($this->params, ResourceInterface::FIRST_NAME),
            ResourceInterface::LAST_NAME                   => array_get($this->params, ResourceInterface::LAST_NAME),
            ResourceInterface::PHONE                       => array_get($this->params, ResourceInterface::PHONE),
            ResourceInterface::EMAIL                       => array_get($this->params, ResourceInterface::EMAIL),
            ResourceInterface::ADDRESS                     => array_get($this->params, ResourceInterface::ADDRESS),

            // Reservation details
            ResourceInterface::DESTINATION_DEPARTURE_DATE  => array_get($this->params, ResourceInterface::DESTINATION_DEPARTURE_DATE),
            ResourceInterface::DESTINATION_ARRIVAL_DATE    => array_get($this->params, ResourceInterface::DESTINATION_ARRIVAL_DATE),
            ResourceInterface::LICENSEPLATE                => (array)array_get($this->params, ResourceInterface::LICENSEPLATE),
            ResourceInterface::OPTIONS                     => array_get($this->params, ResourceInterface::OPTIONS),
            ResourceInterface::RETURN_FLIGHT_NUMBER        => array_get($this->params, ResourceInterface::RETURN_FLIGHT_NUMBER),
            ResourceInterface::NUMBER_OF_PERSONS           => array_get($this->params, ResourceInterface::NUMBER_OF_PERSONS),
            ResourceInterface::EXTERNAL_ID                 => array_get($this->params, ResourceInterface::EXTERNAL_ID),
            ResourceInterface::CUSTOMER_REMARKS            => array_get($this->params, ResourceInterface::CUSTOMER_REMARKS),
            ResourceInterface::PAYMENT_AMOUNT              => array_get($this->params, ResourceInterface::PAYMENT_AMOUNT),
            ResourceInterface::NUMBER_OF_CARS              => array_get($this->params, ResourceInterface::NUMBER_OF_CARS),
            ResourceInterface::PRICE                       => array_get($this->params, ResourceInterface::PRICE),
            ResourceInterface::ORIGIN_GOOGLE_PLACE_ID      => array_get($this->params, ResourceInterface::ORIGIN_GOOGLE_PLACE_ID),
            ResourceInterface::DESTINATION_GOOGLE_PLACE_ID => array_get($this->params, ResourceInterface::DESTINATION_GOOGLE_PLACE_ID),
            ResourceInterface::COSTFREE_CANCELLATION       => array_get($this->params, ResourceInterface::COSTFREE_CANCELLATION),
            ResourceInterface::ONE_WAY                     => array_get($this->params, ResourceInterface::ONE_WAY),
            ResourceInterface::RESERVATION_KEY             => array_get($this->params, ResourceInterface::RESERVATION_KEY),

            // Status
            ResourceInterface::RESERVATION_STATUS          => Travel::RESERVATION_STATUS_PENDING,
            ResourceInterface::PAYMENT_STATUS              => $skipPayment ? ResourceValue::PAYMENT_DEFERRED : ResourceValue::PAYMENT_STATUS_UNKNOWN,

            // Settings
            ResourceInterface::IS_TEST_ORDER               => array_get($this->params, ResourceInterface::IS_TEST_ORDER, ((app()->configure('resource_travel')) ? '' : config('resource_travel.test'))),
            ResourceInterface::USE_DIRECT_RESERVATION      => ((app()->configure('resource_travel')) ? '' : config('resource_travel.use_direct_reservation')),
        ];

        $order = DocumentHelper::update('order', 'travel', $order->__id, $orderData)->product();

        if (strtotime($order[ResourceInterface::DESTINATION_ARRIVAL_DATE] .' UTC') < strtotime('now')) {
            $this->addErrorMessage(ResourceInterface::DESTINATION_ARRIVAL_DATE, 'travel.error.destination_arrival_time_in_past', 'Arrival time must be in the future.');
            return;
        }

        if ($order[ResourceInterface::COSTFREE_CANCELLATION] && strtotime($order[ResourceInterface::DESTINATION_ARRIVAL_DATE] .' UTC') < strtotime('+1 day')) {
            $this->addErrorMessage('costfree_cancellation', 'travel.error.costfree_cancellation_not_possible', 'Costfree cancellation option not available within 24 hours of start.');
            return;
        }

        // Fetch product, and add any additional information to the order
        $calculatedProduct = TravelWrapperAbstractRequest::getProductForOrder(array_merge($order->toArray(), $orderData));
        if ($calculatedProduct === false) {
            $this->addErrorMessage('agree_policy_conditions', 'travel.error.product_unavailable', 'This product is currently not available, our apologies.');
            return;
        }

        // Comparing string to float can lead to floating point imprecisions, so we round them
        if (isset($this->params[ResourceInterface::PRICE]) && round($calculatedProduct[ResourceInterface::PRICE_ACTUAL], 2) != round($this->params[ResourceInterface::PRICE], 2)) {
            $this->addErrorMessage('agree_policy_conditions', 'travel.error.product_price_changed', 'The given price does not match the calculated price.');
            return;
        }

        $optionsPriceTotal = null;
        foreach ($calculatedProduct[ResourceInterface::OPTIONS] as $option) {
            if (in_array($option[ResourceInterface::ID], explode(',', $orderData[ResourceInterface::OPTIONS])))
                $optionsPriceTotal += $option[ResourceInterface::COST];
        }

        $user = User::find(array_get($this->params, ResourceInterface::USER));
        if (is_null($user)) {
            $this->addErrorMessage('user', 'travel.error.user', 'Unable to find the given user.');
            return;
        }

        $vat = !is_null($user->vat) ? $user->vat : 0.0;

        $additionalOrderData = [
            ResourceInterface::AMOUNT                      => $calculatedProduct[ResourceInterface::PRICE_ACTUAL],
            ResourceInterface::PRICE                       => $calculatedProduct[ResourceInterface::PRICE_ACTUAL],
            ResourceInterface::DESCRIPTION                 => array_get($calculatedProduct, ResourceInterface::TITLE),
            ResourceInterface::PRICE_BASE                  => $calculatedProduct[ResourceInterface::PRICE_INITIAL],
            ResourceInterface::PRICE_ADMINISTRATION_FEE    => array_get($calculatedProduct, ResourceInterface::PRICE_ADMINISTRATION_FEE),
            ResourceInterface::PRICE_COSTFREE_CANCELLATION => $orderData[ResourceInterface::COSTFREE_CANCELLATION] ? array_get($calculatedProduct, ResourceInterface::PRICE_COSTFREE_CANCELLATION) : null,
            ResourceInterface::PRICE_OPTIONS               => $optionsPriceTotal,
            ResourceInterface::PROVIDER_ID                 => array_get($calculatedProduct, ResourceInterface::COMPANY .'.'. ResourceInterface::PROVIDER_ID),
            ResourceInterface::PRODUCT                     => json_encode($calculatedProduct),
            ResourceInterface::RESERVATION_KEY             => array_get($calculatedProduct, ResourceInterface::RESERVATION_KEY, array_get($order->toArray(), ResourceInterface::RESERVATION_KEY)),
            ResourceInterface::VAT                         => $vat,
        ];


        //Save the yield/commission fields
        //Get the yields
        $yields = TravelListener::assembleYields(new \ArrayObject(
            [
                ResourceInterface::USER => $this->params[ResourceInterface::USER],
                ResourceInterface::WEBSITE => $this->params[ResourceInterface::WEBSITE]
            ]));

        $additionalOrderData['int_yield_price'] = 0; //$additionalOrderData[ResourceInterface::PRICE_BASE];

        //Is there an internal yield for the product's service?
        if(isset($yields['service']['internal'][$calculatedProduct['service']])){
            $additionalOrderData['int_yield'] = $yields['service']['internal'][$calculatedProduct['service']];
            $additionalOrderData['int_yield_price'] = $additionalOrderData[ResourceInterface::PRICE_BASE] * $additionalOrderData['int_yield'];
        }

        $additionalOrderData['reseller_base_price'] = $additionalOrderData[ResourceInterface::PRICE_BASE] + $additionalOrderData['int_yield_price'];

        //Is there an product commission for the user?
        $additionalOrderData['product_commission_price'] = 0;
        if(isset($yields['user']) && $yields['user'] > 0.0){
            $additionalOrderData['product_commission_reseller'] = $yields['user'];
            $additionalOrderData['product_commission_price'] = $additionalOrderData['int_yield_price'] * $yields['user'];
        }

        $additionalOrderData['reseller_total'] = $additionalOrderData['reseller_base_price'] + $additionalOrderData['product_commission_price'] + $additionalOrderData[ResourceInterface::PRICE_OPTIONS] + $additionalOrderData[ResourceInterface::PRICE_COSTFREE_CANCELLATION];

        $additionalOrderData['vat_price'] = $additionalOrderData['product_commission_price'] * (1.0 + $vat);
        $additionalOrderData['vat_amount'] = $additionalOrderData['vat_price'] - $additionalOrderData['product_commission_price'];

        // Save all the stuff we already know
        $order = DocumentHelper::update('order', 'travel', $order->__id, $additionalOrderData)->product();

        if (!$order[ResourceInterface::USE_DIRECT_RESERVATION]) {
            // Only run the order through ParkingCI if we do not do direct reservation
            $order = $this->createParkingCiOrder($order);
        }

        // Update the order status (triggers any remote reservations when payment is done)
        $order = $this->updatePaymentStatus($order);

        // Clean up result
        $this->result = [
            ResourceInterface::ORDER_ID           => $order[ResourceInterface::ORDER_ID],
            ResourceInterface::RESERVATION_STATUS => $order[ResourceInterface::RESERVATION_STATUS],
            ResourceInterface::PAYMENT_STATUS     => $order[ResourceInterface::PAYMENT_STATUS],
            ResourceInterface::SKIP_PAYMENT       => $skipPayment,
        ];
    }

    protected function updatePaymentStatus($order)
    {
        if (in_array($order[ResourceInterface::PAYMENT_STATUS], [ResourceValue::PAYMENT_SUCCESS, ResourceValue::PAYMENT_DEFERRED])) {
            try {
                // Updating payment status will trigger actual remote API calls for creation
                ResourceHelper::callResource2('updatepaymentstatus.travel', [ResourceInterface::ORDER_ID => $order->order_id]);
            }
            catch (\Exception $e) {
                // Failure :[
            }
            // Refresh order because updatepaymentstatus may have changed it
            $order = self::getOrderByOrderId($order->order_id);
        }
        return $order;
    }

    /**
     * Note: this method can be removed once the Parking CodeIgniter is removed when USE_DIRECT_RESERVATION is on for every environment.
     */
    protected function createParkingCiOrder($order)
    {
        // Create (multiple if needed) Parking CI orders
        $remoteOrders = $this->createParkingCiOrders($order);

        if (preg_match('~product with park_id .* is not active~', $this->getErrorString())) {
            $this->clearErrors();
            $this->addErrorMessage('agree_policy_conditions', 'travel.error.product_not_active', 'This product is currently inactive, our apologies.');
        }

        // Use external order ID, or generate own semi-random order ID.
        $newOrderId = $order->order_id;
        if (!$this->hasErrors()) {
            if (count($remoteOrders) == 1) {
                // We use the parking_ci order id to be the customer-facing non-incremental order id.
                $newOrderId = $remoteOrders[0][ResourceInterface::ORDER_ID];
            } else {
                // Make own order ID, because an Order ID cannot be too long (for multisafepay, etc)
                // Actual order IDs still stored in order->reservation_result
                $newOrderId = 'multi_' . rand(10000001, 99999999);
            }
        }

        $order = DocumentHelper::update('order', 'travel', $order->__id, [
            ResourceInterface::ORDER_ID           => $newOrderId,
            ResourceInterface::RESERVATION_STATUS => $this->hasErrors() ? Travel::RESERVATION_STATUS_ERROR : Travel::RESERVATION_STATUS_PENDING,
            ResourceInterface::RESERVATION_RESULT => json_encode($remoteOrders),
        ])->product();

        return $order;
    }

    protected function generateRandomUniqueOrderId()
    {
        for ($i = 0; $i < 20; $i++) {
            $orderId = rand(100000001, 999999999);

            $orders = DocumentHelper::get('order', 'travel', ['filters' => [ResourceInterface::ORDER_ID => $orderId]]);

            if (count($orders['documents']) == 0)
                return $orderId;
        }

        throw new \Exception('Could not generate unique random order id!');
    }

    protected function createRemoteOrders(Document $order)
    {
        // Construct all orders
        $remoteOrders = self::getParkingCiOrdersParams($order->toArray());

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

        return $orderResults;
    }

    /**
     * Note: this method can be removed once the Parking CodeIgniter is removed when USE_DIRECT_RESERVATION is on for every environment.
     */
    protected function createParkingCiOrders(Document $order)
    {
        // Construct all orders
        $parkingCiOrders = self::getParkingCiOrdersParams($order->toArray());

        // Attempt to create all orders
        $orderResults = [];
        $failedOrder  = false;
        foreach($parkingCiOrders as $order){
            $orderResult    = $this->internalRequest('parkingci', 'create_reservation', $order, true);
            $orderResults[] = $orderResult;

            if($this->resultHasError($orderResult)){
                $failedOrder = true;
                break;
            }
        }

        // Cancel all orders if one failed
        if($failedOrder){
            foreach($orderResults as $orderResult){
                if(isset($orderResult[ResourceInterface::ORDER_ID])){
                    $cancelResult = $this->internalRequest('parkingci', 'cancel_reservation', [ResourceInterface::ORDER_ID => $orderResult[ResourceInterface::ORDER_ID]], true);

                    if($this->resultHasError($cancelResult)){
                        Log::error('Could not cancel Parking CI order #' . $orderResult[ResourceInterface::ORDER_ID] . ' - ' . json_encode($cancelResult));
                    }
                }
            }
        }

        // Set any field-message errors
        foreach($orderResults as $orderResult){
            if(isset($orderResult['error'])){
                $this->setErrorString($orderResult['error']);
            }else if(isset($orderResult['error_messages'])){
                foreach($orderResult['error_messages'] as $message){
                    // Map some fields back from ParkingCI
                    if($message['field'] == ResourceInterface::DEPARTURE_DATE){
                        $message['field'] = ResourceInterface::DESTINATION_ARRIVAL_DATE;
                    }
                    if($message['field'] == ResourceInterface::ARRIVAL_DATE){
                        $message['field'] = ResourceInterface::DESTINATION_DEPARTURE_DATE;
                    }
                    $this->addErrorMessage($message['field'], $message['code'], $message['message']);
                }
            }
        }

        return $orderResults;
    }
}