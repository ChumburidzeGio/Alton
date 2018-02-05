<?php

namespace App\Resources\Travel\Methods;


use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class StartPayment extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $order = $this->getOrderByOrderId($this->params[ResourceInterface::ORDER_ID]);

        if (!$order)
            return;

        //Call payment methods to get fees
        $metadataResult = ResourceHelper::callResource2('paymentmethods.payment.multisafepay', array_merge($this->params, [
            ResourceInterface::PRODUCT_TYPE => 'travel',
            ResourceInterface::PRICE => $order->{ResourceInterface::AMOUNT},
        ]), true);

        //Search for the payment metadata of the given payment method
        $foundArray = $this->searchSubArray($metadataResult, ResourceInterface::PAYMENT_METHOD, $this->params[ResourceInterface::PAYMENT_METHOD]);
        if($foundArray !== false && $order->{ResourceInterface::TRANSACTION_COSTS} == null){
            //Payment metadata was found, add the transaction costs to the order amount
            $amount = $order->{ResourceInterface::AMOUNT} + $foundArray[ResourceInterface::PRICE_FEE];
            //Update the order with the new amount, saving the payment method and transaction hosts for historical reasons
            DocumentHelper::update('order', 'travel', $order->__id, [
                ResourceInterface::AMOUNT => $amount,
                ResourceInterface::PAYMENT_METHOD => $this->params[ResourceInterface::PAYMENT_METHOD],
                ResourceInterface::TRANSACTION_COSTS => $foundArray[ResourceInterface::PRICE_FEE],
            ]);
        }

        // We are deferring payment (aka 'nopay', aka 'skip_payment'), so redirect to self
        if ($order[ResourceInterface::PAYMENT_STATUS] == ResourceValue::PAYMENT_DEFERRED)
        {
            $this->result = [
                ResourceInterface::REDIRECT_URL => $this->params[ResourceInterface::PAYMENT_RETURN_URL],
                ResourceInterface::STATUS => ResourceValue::PAYMENT_DEFERRED,
            ];
        }
        else
        {
            $this->result = $this->internalRequest('payment.multisafepay', 'startorderpayment', array_merge($this->params, [
                ResourceInterface::PRODUCT_TYPE => 'travel',
                ResourceInterface::ORDER_ID => $order->__id,
            ]), true);

            if ($this->resultHasError($this->result)) {
                $this->setErrorString('Multisafepay error: ' . json_encode($this->result));
            }
        }
    }

    public function searchSubArray(Array $array, $key, $value) {
        foreach ($array as $subarray){
            if (isset($subarray[$key]) && $subarray[$key] == $value)
                return $subarray;
        }
        return false;
    }
}