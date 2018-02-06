<?php

namespace App\Resources\Multisafepay\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Multisafepay\AbstractMultiSafepayRequest;

class PaymentMethods extends AbstractMultiSafepayRequest
{
    protected $inputTransformations = [
        ResourceInterface::AMOUNT => 'convertToCents',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::COUNTRY_CODE => 'country',
        ResourceInterface::CURRENCY => 'currency',
        ResourceInterface::AMOUNT => 'amount',
        ResourceInterface::LANGUAGE => 'locale',
    ];
    protected $externalToResultMapping = [
        'id' => ResourceInterface::PAYMENT_METHOD,
        'description' => ResourceInterface::NAME,
    ];
    protected $resultTransformations = [
        ResourceInterface::BRAND_LOGO => 'getBrandLogo',
        ResourceInterface::BRAND_LOGO_THUMB => 'getBrandLogoThumb',
    ];

    protected $logosAvailable = [
        'VISA',
        'MASTERCARD',
        'IDEAL',
        'BANKTRANS',
        'DIRECTBANK', // Is Sofort Banking
        'DOTPAY',
        'KLARNA',
        'GIROPAY',
        'MISTERCASH', // Is Bancontact
        'PAYPAL',
    ];

    protected $transactionFees = [
        'VISA' => [
          'constant' => 0,
          'percentage' => 0.015,
        ],
        'MASTERCARD' => [
            'constant' => 0,
            'percentage' => 0.015,
        ],
        'MISTERCASH' => [ // Is Bancontact
            'constant' => 0.10,
            'percentage' => 0.015,
        ],
        'DIRECTBANK' => [ // Is Sofort Banking
            'constant' => 0.15,
            'percentage' => 0.01,
        ],
        'GIROPAY' => [
            'constant' => 0.20,
            'percentage' => 0.015,
        ],
        'IDEAL' => [
            'constant' => 0.0,
            'percentage' => 0.0,
        ],
        'PAYPAL' => [
            'constant' => 0.35,
            'percentage' => 0.045,
        ],
    ];

    public function __construct()
    {
        parent::__construct('gateways');
    }

    public function setParams(array $params)
    {
        $this->orderAmount = isset($params['price']) ? $params['price'] : 0;
        return parent::setParams($params);
    }

    public function getResult()
    {
        $result = parent::getResult();
        foreach ($result as &$resultItem){
            $resultItem['fee'] = ceil($this->calculateTransactionFee($resultItem['payment_method'], $this->orderAmount) * 100) / 100;
        }
        return $result;
    }

    protected function getBrandLogo($value, $paymentMethod)
    {
        if (in_array($paymentMethod[ResourceInterface::PAYMENT_METHOD], $this->logosAvailable))
            return $this->settings['logo_base_url'] . 'paymentmethods/' . $paymentMethod[ResourceInterface::PAYMENT_METHOD] .'.png';
        else
            return null;
    }

    protected function getBrandLogoThumb($value, $paymentMethod)
    {
        if (in_array($paymentMethod[ResourceInterface::PAYMENT_METHOD], $this->logosAvailable))
            return $this->settings['logo_base_url'] . 'paymentmethods/' . $paymentMethod[ResourceInterface::PAYMENT_METHOD] .'_thumb.png';
        else
            return null;
    }

    private function calculateTransactionFee($provider , $price)
    {
        if(!isset($this->transactionFees[$provider])){
            return 0.0;
        }
        $formula = $this->transactionFees[$provider];

        $percentagePart = $price * $formula['percentage'];

        return $percentagePart + $formula['constant'];
    }
}