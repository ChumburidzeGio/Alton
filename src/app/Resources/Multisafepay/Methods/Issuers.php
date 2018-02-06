<?php

namespace App\Resources\Multisafepay\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Multisafepay\AbstractMultiSafepayRequest;

class Issuers extends AbstractMultiSafepayRequest
{
    protected $inputToExternalMapping = [
        ResourceInterface::LANGUAGE => 'locale',
    ];
    protected $externalToResultMapping = [
        'code' => ResourceInterface::PAYMENT_METHOD_ISSUER,
        'description' => ResourceInterface::NAME,
    ];
    protected $resultTransformations = [
        ResourceInterface::BRAND_LOGO => 'getBrandLogo',
        ResourceInterface::BRAND_LOGO_THUMB => 'getBrandLogoThumb',
    ];

    public function __construct()
    {
        parent::__construct('issuers/{payment_method}');
    }

    public function setParams(array $params)
    {
        return parent::setParams(array_merge([
            ResourceInterface::PAYMENT_METHOD => 'IDEAL',
        ], $params));
    }

    protected function getBrandLogo($value, $paymentMethod)
    {
        return $this->settings['logo_base_url'] . 'idealissuers/' . $paymentMethod[ResourceInterface::PAYMENT_METHOD_ISSUER] .'.png';
    }

    protected function getBrandLogoThumb($value, $paymentMethod)
    {
        return $this->settings['logo_base_url'] . 'idealissuers/' . $paymentMethod[ResourceInterface::PAYMENT_METHOD_ISSUER] .'_thumb.png';
    }
}