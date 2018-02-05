<?php


namespace App\Resources\Parking2\Methods;

use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Parkandfly\Parking2WrapperAbstractRequest;
use App\Resources\Parking2\Parking;

/**
 * @property array product
 */
class Contract extends Parking2WrapperAbstractRequest
{
    public function setParams(Array $params)
    {
        parent::setParams($params);

        $this->product = $this->getProductById($params[ResourceInterface::PRODUCT_ID]);
        if ($this->product) {
            $this->params[ResourceInterface::LOCATION_ID]     = $this->product
                ? $this->product['resource']['id'] : null;
            $this->params[ResourceInterface::RESERVATION_KEY] = $this->product
                ? $this->product[ResourceInterface::RESERVATION_KEY] : null;
        }
    }

    public function executeFunction()
    {

        // Hacky naming mapping
        if(isset($this->params[ResourceInterface::AVAILABLE_OPTIONS])){
            $this->params[ResourceInterface::OPTIONS] = $this->params[ResourceInterface::AVAILABLE_OPTIONS];
        }

        $skipPayment = (bool) $this->getRightSetting('skip_payment', array_get($this->params, ResourceInterface::USER), array_get($this->params, ResourceInterface::WEBSITE));

        //multiple license plates
        $multi = false;
        if(is_array($this->params[ResourceInterface::LICENSEPLATE])){
            $orderIdArr   = [];
            $numberOfCars = max(1, count(array_filter($this->params[ResourceInterface::LICENSEPLATE])));
            foreach(array_filter($this->params[ResourceInterface::LICENSEPLATE]) as $licenseplate){
                $params                                  = $this->params;
                $params[ResourceInterface::PRICE]        = ((double) $params[ResourceInterface::PRICE] / $numberOfCars);
                $params[ResourceInterface::LICENSEPLATE] = $licenseplate;
                $result                                  = $this->internalRequest('parkingci', 'create_reservation', $params, true);
                $this->result[]                          = $result;
                $orderIdArr[]                            = array_get($result, ResourceInterface::ORDER_ID);
                $multi                                   = true;
            }
            $this->params[ResourceInterface::LICENSEPLATE] = implode(",", $this->params[ResourceInterface::LICENSEPLATE]);
            $externalOrderId                               = implode("-", $orderIdArr);
        }else{
            $this->result    = $this->internalRequest('parkingci', 'create_reservation', $this->params, true);
            $externalOrderId = array_get($this->result, ResourceInterface::ORDER_ID);
        }

        if($this->resultHasError($this->result, $multi)){
            $this->setErrorString('Parking CI error: ' . json_encode($this->result));
            if( ! $multi){
                if(isset($this->result['error_messages'])){
                    foreach($this->result['error_messages'] as $message){
                        $this->addErrorMessage($message['field'], $message['code'], $message['message']);
                    }
                }
            }else{
                foreach($this->result as $res){
                    if(isset($res['error_messages'])){
                        foreach($res['error_messages'] as $message){
                            $this->addErrorMessage($message['field'], $message['code'], $message['message']);
                        }
                    }
                }
            }
        }

        $orderId = array_get($this->params, '_forinternaluse.' . ResourceInterface::ORDER_ID);

        DocumentHelper::update('order', 'parking2', $orderId, [
            // We use the 'external' parking_ci order id to be the customer-facing, random, order number - 'order nr'
            ResourceInterface::ORDER_ID             => $externalOrderId,
            ResourceInterface::WEBSITE              => array_get($this->params, ResourceInterface::WEBSITE),
            ResourceInterface::USER                 => array_get($this->params, ResourceInterface::USER),

            // Payment details
            ResourceInterface::AMOUNT               => array_get($this->params, ResourceInterface::PRICE),
            ResourceInterface::DESCRIPTION          => array_get($this->params, ResourceInterface::CUSTOMER_REMARKS),
            ResourceInterface::CURRENCY             => 'EUR',
            ResourceInterface::DESCRIPTION          => array_get($this->product, 'name'),

            // Personal details
            ResourceInterface::FIRST_NAME           => array_get($this->params, ResourceInterface::FIRST_NAME),
            ResourceInterface::LAST_NAME            => array_get($this->params, ResourceInterface::LAST_NAME),
            ResourceInterface::PHONE                => array_get($this->params, ResourceInterface::PHONE),
            ResourceInterface::EMAIL                => array_get($this->params, ResourceInterface::EMAIL),

            // Reservation details
            ResourceInterface::ARRIVAL_DATE         => array_get($this->params, ResourceInterface::ARRIVAL_DATE),
            ResourceInterface::DEPARTURE_DATE       => array_get($this->params, ResourceInterface::DEPARTURE_DATE),
            ResourceInterface::LICENSEPLATE         => array_get($this->params, ResourceInterface::LICENSEPLATE),
            ResourceInterface::OPTIONS              => array_get($this->params, ResourceInterface::OPTIONS),
            ResourceInterface::RETURN_FLIGHT_NUMBER => array_get($this->params, ResourceInterface::RETURN_FLIGHT_NUMBER),
            ResourceInterface::NUMBER_OF_PERSONS    => array_get($this->params, ResourceInterface::NUMBER_OF_PERSONS),
            ResourceInterface::EXTERNAL_ID          => array_get($this->params, ResourceInterface::EXTERNAL_ID),
            ResourceInterface::CUSTOMER_REMARKS     => array_get($this->params, ResourceInterface::CUSTOMER_REMARKS),
            ResourceInterface::PAYMENT_AMOUNT       => array_get($this->params, ResourceInterface::PAYMENT_AMOUNT),

            ResourceInterface::RESERVATION_STATUS => $this->resultHasError($this->result, $multi) ? Parking::RESERVATION_STATUS_ERROR : Parking::RESERVATION_STATUS_PENDING,
            ResourceInterface::RESERVATION_RESULT => json_encode($this->result),

            ResourceInterface::PAYMENT_STATUS => $skipPayment ? ResourceValue::PAYMENT_DEFERRED : ResourceValue::PAYMENT_STATUS_UNKNOWN,
            ResourceInterface::PRODUCT              => json_encode($this->product),
        ]);

        // Clean up result
        $this->result = [
            ResourceInterface::ORDER_ID     => $externalOrderId,
            ResourceInterface::SKIP_PAYMENT => $skipPayment,
        ];
    }
}