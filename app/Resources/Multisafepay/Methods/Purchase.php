<?php

namespace App\Resources\Multisafepay\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Multisafepay\AbstractMultiSafepayRequest;

/**
 * Class Purchase
 *
 * This class covers the '/orders' endpoint for the MultiSafepay REST endpoint.
 * The endpoint is used for several payment methods, and each may require additional parameters to be passed.
 *
 * This implementation focusses on supporting:
 * - Via 'direct' input: iDEAL, PayPal
 * - Via 'redirect' input: MasterCard, VISA, GiroPay
 *
 * @package App\Resources\Multisafepay\Methods
 */
class Purchase extends AbstractMultiSafepayRequest
{
    const PAYMENT_INTERFACE_DEFAULT = 'default';
    const PAYMENT_INTERFACE_DIRECT = 'direct';
    const PAYMENT_INTERFACE_REDIRECT = 'redirect';
    const PAYMENT_INTERFACE_CHECKOUT = 'checkout';

    protected $inputTransformations = [
        ResourceInterface::AMOUNT => 'convertToCents',
        ResourceInterface::LANGUAGE => 'convertLanguageToLocale',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::LANGUAGE => 'locale',

        ResourceInterface::PAYMENT_INTERFACE_TYPE => 'type',
        ResourceInterface::PAYMENT_METHOD => 'gateway',
        ResourceInterface::PAYMENT_METHOD_ISSUER => 'gateway_info.issuer_id',

        ResourceInterface::AMOUNT => 'amount',
        ResourceInterface::ORDER_ID => 'order_id',
        ResourceInterface::DESCRIPTION => 'description',
        ResourceInterface::CURRENCY => 'currency',

        ResourceInterface::PAYMENT_CANCEL_URL => 'payment_options.cancel_url',
        ResourceInterface::PAYMENT_NOTIFY_URL => 'payment_options.notification_url',
        ResourceInterface::PAYMENT_RETURN_URL => 'payment_options.redirect_url',
        ResourceInterface::CLOSE_WINDOW => 'payment_options.close_window',

        ResourceInterface::VALIDITY_PAYMENT_URL_DAYS => 'days_active',
        ResourceInterface::DISABLE_SEND_EMAIL => 'customer.disable_send_email',
        ResourceInterface::GOOGLE_ANALYTICS_CODE => 'google_analytics.account',

        ResourceInterface::MANUAL_CREDITCARD_CHECK => 'manual',

        ResourceInterface::CUSTOM_VAR_1 => 'var1',
        ResourceInterface::CUSTOM_VAR_2 => 'var2',
        ResourceInterface::CUSTOM_VAR_3 => 'var3',

        // Customer data
        ResourceInterface::FIRST_NAME => 'customer.first_name',
        ResourceInterface::LAST_NAME => 'customer.last_name',

        ResourceInterface::POSTAL_CODE => 'customer.zip_code',
        ResourceInterface::HOUSE_NUMBER => 'customer.house_number',
        ResourceInterface::CITY => 'customer.city',
        ResourceInterface::STATE => 'customer.state',
        ResourceInterface::COUNTRY_CODE => 'customer.country',

        ResourceInterface::PHONE => 'customer.phone',
        ResourceInterface::EMAIL => 'customer.email',

        ResourceInterface::LOCALE => 'customer.locale',
        ResourceInterface::IP => 'customer.ip_address',
    ];
    protected $externalToResultMapping = [
        'payment_url' => ResourceInterface::REDIRECT_URL,
        'order_id' => ResourceInterface::ORDER_ID,
        'transaction_id' => ResourceInterface::TRANSACTION_ID,
        'status' => ResourceInterface::STATUS,
    ];

    protected function getDefaultParams()
    {
        return parent::getDefaultParams() + [
            'type' => null,
            'order_id' => null,
            'recurring_id' => null, // Not used. For billing a previous CC.
            'gateway' => null,
            'currency' => 'EUR',
            'amount' => null,
            'description' => null,
            'var1' => null,
            'var2' => null,
            'var3' => null,
            'days_active' => 30,
            'items' => null, //  Not used. HTML of items to list in pages & emails.
            'gateway_info' => [
                // 'IDEAL' only:
                'issuer_id' => null,
                // For 'Direct Debit', 'Banktransfer' payment methods:
                /*
                'account_id' => '', // IBAN number
                */
                // For 'Direct Debit' only:
                /*
                'account_holder_name' => null,
                'account_holder_city' => null,
                'account_holder_country' => null,
                'account_holder_iban' => null,
                'account_holder_bic' => null,
                'emandate'  => null,
                */
                // For 'Pay After Delivery', 'E-Invoicing', 'Klarna':
                /*
                'birthday' => null,
                'bank_account' => null,
                'phone' => null,
                'email' => null,
                'gender' => null,
                'referrer' => null,
                'user_agent' => null,
                */
            ],
            'payment_options'  => [
                'notification_url' => null,
                'redirect_url' => null,
                'cancel_url' => null,
                'close_window' => true,
            ],
            'customer' => [
                // Customer interface / interaction options:
                'locale' => null,
                'disable_send_email' => null,

                // Customer data mainly required for 'manual' Credit Card checks:
                'ip_address' => null,
                'forwarded_ip' => null,
                'first_name' => null,
                'last_name' => null,
                'address1' => null,
                'address2' => null,
                'house_number' => null,
                'zip_code' => null,
                'city' => null,
                'state' => null,
                'country' => null,
                'phone' => null,
                'email' => null,
            ],
            // Options for 'Pay After Delivery', 'E-Invoicing' or 'Klarna'
            // See https://www.multisafepay.com/documentation/doc/API-Reference/ for details
            /*
            'shopping_cart' => [],
            'checkout_options' => [],
            */
            'google_analytics'  => [
                'account' => null,
            ],
        ];
    }

    public function __construct()
    {
        parent::__construct('orders', self::METHOD_POST);
    }

    public function setParams(Array $params)
    {
        // Default to 'direct' payment type if available
        if (empty($params[ResourceInterface::PAYMENT_INTERFACE_TYPE]) || $params[ResourceInterface::PAYMENT_INTERFACE_TYPE] == self::PAYMENT_INTERFACE_DEFAULT) {
            $params[ResourceInterface::PAYMENT_INTERFACE_TYPE] = in_array($params[ResourceInterface::PAYMENT_METHOD], $this->directPaymentMethods) ? self::PAYMENT_INTERFACE_DIRECT : self::PAYMENT_INTERFACE_REDIRECT;
        }

        if ($params[ResourceInterface::PAYMENT_INTERFACE_TYPE] == self::PAYMENT_INTERFACE_DIRECT
            && !in_array($params[ResourceInterface::PAYMENT_METHOD], $this->directPaymentMethods)) {
            $this->setErrorString('Cannot use `'. $params[ResourceInterface::PAYMENT_INTERFACE_TYPE] .'` payment interface with payment method `'. $params[ResourceInterface::PAYMENT_METHOD] .'`');
        }

        parent::setParams($params);
    }
}