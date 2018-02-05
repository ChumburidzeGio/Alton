<?php

namespace App\Resources\Multisafepay\Methods;

use App\Helpers\DocumentHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Multisafepay\AbstractMultiSafepayMetaRequest;
use App\Resources\Multisafepay\Payment;

class UpdateOrderPaymentStatus extends AbstractMultiSafepayMetaRequest
{
    protected $orderDataKeys = [
        // Order data
        ResourceInterface::PRODUCT_TYPE => null,
        ResourceInterface::ORDER_ID => null,
    ];

    public function getDefaultParams()
    {
        return [
            ResourceInterface::PRODUCT_TYPE => null,
            ResourceInterface::ORDER_ID => null,
        ];
    }

    public function executeFunction()
    {
        // Retrieve order
        try {
            $order = DocumentHelper::show('order', $this->params[ResourceInterface::PRODUCT_TYPE], $this->params[ResourceInterface::ORDER_ID]);
        } catch (\Exception $e) {
            $this->setErrorString('Cannot find order `' . $this->params[ResourceInterface::ORDER_ID] . '` of type `' . $this->params[ResourceInterface::PRODUCT_TYPE] . '`');
            return;
        }

        $paymentResult = $this->internalRequest('payment.multisafepay', 'getpayment', [
            ResourceInterface::API_KEY => array_get($this->params, ResourceInterface::API_KEY),
            ResourceInterface::USER => $order->user,
            ResourceInterface::WEBSITE => $order->website,
            ResourceInterface::ORDER_ID => $order[ResourceInterface::ORDER_ID],
        ], true);

        $newMultisafePayStatus = isset($paymentResult[ResourceInterface::STATUS]) ? $paymentResult[ResourceInterface::STATUS] : Payment::PAYMENT_STATUS_ERROR;

        $paymentStatus = Payment::getGeneralStatus($newMultisafePayStatus);

        // Only update amount paid, when going from any non-success status to payment success status.
        $amountPaid = $order[ResourceInterface::PAYMENT_AMOUNT_PAID];
        if ($order[ResourceInterface::PAYMENT_STATUS] != ResourceValue::PAYMENT_SUCCESS && $paymentStatus == ResourceValue::PAYMENT_SUCCESS) {
            $amountPaid = $order[ResourceInterface::AMOUNT];
        }

        DocumentHelper::update('order', $this->params[ResourceInterface::PRODUCT_TYPE], $order->__id, [
            ResourceInterface::PAYMENT_STATUS => $paymentStatus,
            ResourceInterface::PAYMENT_AMOUNT_PAID => $amountPaid,
            ResourceInterface::PAYMENT_STATUS_MULTISAFEPAY => $newMultisafePayStatus,
            ResourceInterface::PAYMENT_RESULT => $paymentResult,
        ]);

        $this->result = [
            ResourceInterface::STATUS => $newMultisafePayStatus,
            '@unmapped' => $paymentResult,
        ];
    }
}