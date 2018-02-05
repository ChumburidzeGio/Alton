<?php

namespace App\Resources\Multisafepay\Methods;

use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Website;
use App\Resources\Multisafepay\AbstractMultiSafepayMetaRequest;
use App\Resources\Multisafepay\Payment;

class StartOrderPayment extends AbstractMultiSafepayMetaRequest
{
    protected $orderDataKeys = [
        // Order data
        ResourceInterface::AMOUNT,
        ResourceInterface::CURRENCY,
        ResourceInterface::DESCRIPTION,

        // Customer data
        ResourceInterface::FIRST_NAME,
        ResourceInterface::LAST_NAME,
        ResourceInterface::POSTAL_CODE,
        ResourceInterface::HOUSE_NUMBER,
        ResourceInterface::CITY,
        ResourceInterface::STATE,
        ResourceInterface::COUNTRY_CODE,
        ResourceInterface::PHONE,
        ResourceInterface::EMAIL,
        ResourceInterface::LOCALE,
        ResourceInterface::IP,
    ];

    public function getDefaultParams()
    {
        return [
            ResourceInterface::PRODUCT_TYPE => null,
            ResourceInterface::ORDER_ID => null,

            ResourceInterface::PAYMENT_METHOD => null,
            ResourceInterface::PAYMENT_METHOD_ISSUER => null,

            ResourceInterface::LOCALE => null,
            ResourceInterface::LANGUAGE => null,

            ResourceInterface::PAYMENT_CANCEL_URL => null,
            ResourceInterface::PAYMENT_NOTIFY_URL => null,
            ResourceInterface::PAYMENT_RETURN_URL => null,
        ];
    }

    public function executeFunction()
    {
        try{
            $order = DocumentHelper::show('order', $this->params[ResourceInterface::PRODUCT_TYPE], $this->params[ResourceInterface::ORDER_ID]);
        }catch(\Exception $e){
            $this->setErrorString('Cannot find order `'. $this->params[ResourceInterface::ORDER_ID] .'` of type `'. $this->params[ResourceInterface::PRODUCT_TYPE].'`');
            return ;
        }

        DocumentHelper::update('order', $this->params[ResourceInterface::PRODUCT_TYPE], $this->params[ResourceInterface::ORDER_ID], [
            ResourceInterface::PAYMENT_STATUS => Payment::getGeneralStatus(Payment::PAYMENT_STATUS_REQUESTING),
            ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY => Payment::PAYMENT_STATUS_REQUESTING,
            ResourceInterface::PAYMENT_INPUT => $this->params,
        ]);

        if (!isset($this->params[ResourceInterface::LOCALE]) && !isset($this->params[ResourceInterface::LANGUAGE])) {
            $website = Website::find($order->website);
            $this->params[ResourceInterface::LANGUAGE] = $website ? $website->language : null;
        }

        // Do Purchase method stuff
        $orderData = array_filter(array_only($order->toArray(), $this->orderDataKeys));
        $purchaseData = [
            // Use custom `order_id` in order object, if present.
            ResourceInterface::API_KEY => array_get($this->params, ResourceInterface::API_KEY),
            ResourceInterface::USER => $order[ResourceInterface::USER],
            ResourceInterface::WEBSITE => $order[ResourceInterface::WEBSITE],
            ResourceInterface::ORDER_ID => isset($order[ResourceInterface::ORDER_ID]) ? $order[ResourceInterface::ORDER_ID] : $this->params[ResourceInterface::ORDER_ID],
            ResourceInterface::PAYMENT_METHOD => $this->params[ResourceInterface::PAYMENT_METHOD],
            ResourceInterface::PAYMENT_METHOD_ISSUER => $this->params[ResourceInterface::PAYMENT_METHOD_ISSUER],
            ResourceInterface::PAYMENT_CANCEL_URL => $this->params[ResourceInterface::PAYMENT_CANCEL_URL],
            ResourceInterface::PAYMENT_NOTIFY_URL => $this->params[ResourceInterface::PAYMENT_NOTIFY_URL],
            ResourceInterface::PAYMENT_RETURN_URL => $this->params[ResourceInterface::PAYMENT_RETURN_URL],
            ResourceInterface::LOCALE => $this->params[ResourceInterface::LOCALE],
            ResourceInterface::LANGUAGE => $this->params[ResourceInterface::LANGUAGE],
        ] + $orderData;
        $this->result = $this->internalRequest('payment.multisafepay', 'purchase', $purchaseData, true);

        $multiSafepayStatus = $this->resultHasError($this->result) ? Payment::PAYMENT_STATUS_ERROR : $this->result[ResourceInterface::STATUS];

        DocumentHelper::update('order', $this->params[ResourceInterface::PRODUCT_TYPE], $this->params[ResourceInterface::ORDER_ID], [
            ResourceInterface::PAYMENT_STATUS => Payment::getGeneralStatus(Payment::PAYMENT_STATUS_REQUESTING),
            ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY => $multiSafepayStatus,
            ResourceInterface::PAYMENT_RESULT => $this->result,
            ResourceInterface::PAYMENT_TRANSACTION_ID => !empty($this->result[ResourceInterface::TRANSACTION_ID]) ? $this->result[ResourceInterface::TRANSACTION_ID] : null,
        ]);

        if ($this->resultHasError($this->result)) {
            $this->setErrorString('Purchase error: ' . json_encode($this->result));
            return;
        }
    }
}