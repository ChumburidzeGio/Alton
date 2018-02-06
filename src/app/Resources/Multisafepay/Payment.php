<?php

namespace App\Resources\Multisafepay;

use App\Interfaces\ResourceValue;
use App\Resources\AbstractServiceRequest;

class Payment extends AbstractServiceRequest
{
    // Payment status source docs: https://www.multisafepay.com/documentation/doc/API-Reference/

    const PAYMENT_STATUS_INITALIZED = 'initialized';                // A payment link has been generated, but no payment has been received yet.
    const PAYMENT_STATUS_DECLINED = 'declined';                     // Rejected by the credit card company.
    const PAYMENT_STATUS_CANCELED = 'canceled';                     // Canceled by the merchant (only applies to the status Initialised or Uncleared).
    const PAYMENT_STATUS_COMPLETED = 'completed';                   // Payment has been successfully completed.
    const PAYMENT_STATUS_EXPIRED = 'expired';                       // Depending on the payment method unfinished transactions automatically expire after a predefined period.
    const PAYMENT_STATUS_UNCLEARED = 'uncleared';                   // Waiting for manual permission of the merchant to approve/disapprove the payment.
    const PAYMENT_STATUS_REFUNDED = 'refunded';                     // Payment has been refunded to the customer.
    const PAYMENT_STATUS_PARTIAL_REFUNDED = 'partial_refunded';     // The payment has been partially refunded to the customer.
    const PAYMENT_STATUS_RESERVED = 'reserved';                     // Payout/refund has been temporary put on reserved, a temporary status, till the e-wallet has been checked on sufficient balance.
    const PAYMENT_STATUS_VOID = 'void';                             // Failed payment.
    const PAYMENT_STATUS_CHARGEDBACK = 'chargedback';               // Forced reversal of funds initiated by consumer’s issuing bank. Only applicable to direct debit and credit card payments.

    // Komparu custom status
    const PAYMENT_STATUS_REQUESTING = 'requesting';                 // Requesting payment initialization from MultiSafepay
    const PAYMENT_STATUS_ERROR = 'error';                           // Error during multisafepay API call

    protected static $generalStatus = [
        // In progress
        self::PAYMENT_STATUS_INITALIZED => ResourceValue::PAYMENT_IN_PROGRESS,
        self::PAYMENT_STATUS_UNCLEARED => ResourceValue::PAYMENT_IN_PROGRESS,
        self::PAYMENT_STATUS_RESERVED => ResourceValue::PAYMENT_IN_PROGRESS,
        self::PAYMENT_STATUS_REQUESTING => ResourceValue::PAYMENT_IN_PROGRESS,

        // Success
        self::PAYMENT_STATUS_COMPLETED => ResourceValue::PAYMENT_SUCCESS,

        // Failure (not paid or refunded)
        self::PAYMENT_STATUS_DECLINED => ResourceValue::PAYMENT_FAILED,
        self::PAYMENT_STATUS_CANCELED => ResourceValue::PAYMENT_FAILED,
        self::PAYMENT_STATUS_EXPIRED => ResourceValue::PAYMENT_FAILED,
        self::PAYMENT_STATUS_VOID => ResourceValue::PAYMENT_FAILED,
        self::PAYMENT_STATUS_CHARGEDBACK => ResourceValue::PAYMENT_FAILED,
        self::PAYMENT_STATUS_REFUNDED => ResourceValue::PAYMENT_FAILED,
        self::PAYMENT_STATUS_PARTIAL_REFUNDED => ResourceValue::PAYMENT_FAILED,

        // Unknown
        self::PAYMENT_STATUS_ERROR => ResourceValue::PAYMENT_STATUS_UNKNOWN,
    ];

    protected $methodMapping = [
        'paymentmethods' => [
            'class'       => \App\Resources\Multisafepay\Methods\PaymentMethods::class,
            'description' => 'Get available payment methods.',
        ],
        'issuers' => [
            'class'       => \App\Resources\Multisafepay\Methods\Issuers::class,
            'description' => 'Get available issuers.',
        ],
        'purchase' => [
            'class'       => \App\Resources\Multisafepay\Methods\Purchase::class,
            'description' => 'Do a purchase.',
        ],
        'getpayment' => [
            'class'       => \App\Resources\Multisafepay\Methods\GetPayment::class,
            'description' => 'Get the status of a payment.',
        ],
        // Order related methods
        'startorderpayment' => [
            'class'       => \App\Resources\Multisafepay\Methods\StartOrderPayment::class,
            'description' => 'Start payment process for an existing order.',
        ],
        'updateorderpaymentstatus' => [
            'class'       => \App\Resources\Multisafepay\Methods\UpdateOrderPaymentStatus::class,
            'description' => 'Update the payment status of an existing order.',
        ],
    ];

    public static function getGeneralStatus($status)
    {
        return array_get(self::$generalStatus, $status, ResourceValue::PAYMENT_STATUS_UNKNOWN);
    }
}
