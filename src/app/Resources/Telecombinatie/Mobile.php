<?php
/**
 * User: Roeland Werring
 * Date: 15/04/15
 * Time: 23:05
 * 
 */

namespace App\Resources\Telecombinatie;

class Mobile extends TelecombinatieServiceRequest
{
    protected $serviceProvider = 'mobile1';

    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Telecombinatie\Methods\Impl\MobileProductList::class,
            'description' => 'Request list of products'
        ],
        'providers'     => [
            'class'       => \App\Resources\Telecombinatie\Methods\Impl\ProviderList::class,
            'description' => 'Request list of providers, code as key'
        ],
        'networks'     => [
            'class'       => \App\Resources\Telecombinatie\Methods\Impl\NetworkList::class,
            'description' => 'Request list of networks'
        ],
        'smscode'     => [
            'class'       => \App\Resources\Telecombinatie\Methods\Impl\SmsCode::class,
            'description' => 'Get a code to authenticate retetion'
        ],
        'contract'     => [
            'class'       => \App\Resources\Telecombinatie\Methods\Impl\MobileContract::class,
            'description' => 'Post a lead to TeleCombinatie'
        ],
        'payment'     => [
            'class'       => \App\Resources\Telecombinatie\Methods\Impl\Payment::class,
            'description' => 'Start iDeal payment for TeleCombinatie'
        ],


    ];
}