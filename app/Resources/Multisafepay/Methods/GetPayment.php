<?php

namespace App\Resources\Multisafepay\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Multisafepay\AbstractMultiSafepayRequest;

/**
 * Class GetPaymentStatus
 *
 * Status:
 *
 * initialized  - A payment link has been generated, but no payment has been received yet.
 * declined     - Rejected by the credit card company.
 * canceled     - Canceled by the merchant (only applies to the status Initialised or Uncleared).
 * completed    - Payment has been successfully completed.
 * expired      - Depending on the payment method unfinished transactions automatically expire after a predefined period.
 * uncleared    - Waiting for manual permission of the merchant to approve/disapprove the payment.
 * refunded     - Payment has been refunded to the customer.
 * partial_Refunded - The payment has been partially refunded to the customer.
 * reserved     - Payout/refund has been temporary put on reserved, a temporary status, till the e-wallet has been checked on sufficient balance.
 * void         - Failed payment.
 * chargedback  - Forced reversal of funds initiated by consumer’s issuing bank. Only applicable to direct debit and credit card payments.
 *
 * @package App\Resources\Multisafepay\Methods
 */
class GetPayment extends AbstractMultiSafepayRequest
{
    protected $inputToExternalMapping = [
        ResourceInterface::ORDER_ID => 'order_id',
        ResourceInterface::LANGUAGE => 'locale',
    ];
    protected $externalToResultMapping = [
        'order_id' => ResourceInterface::ORDER_ID,
        'transaction_id' => ResourceInterface::TRANSACTION_ID,
        'amount' => ResourceInterface::AMOUNT,
        'currency' => ResourceInterface::CURRENCY,
        'description' => ResourceInterface::DESCRIPTION,
        'status' => ResourceInterface::STATUS,
        'customer.first_name' => ResourceInterface::FIRST_NAME,
        'customer.last_name' => ResourceInterface::LAST_NAME,
        'customer.email' => ResourceInterface::EMAIL,
        'payment_details.type' => ResourceInterface::PAYMENT_METHOD,
    ];
    protected $resultTransformations = [
        ResourceInterface::AMOUNT => 'convertFromCents',
    ];

    public function __construct()
    {
        parent::__construct('orders/{order_id}', self::METHOD_GET);
    }


    protected function convertFromCents($value)
    {
        return (int)$value / 100;
    }
}