<?php
namespace App\Resources\Moneyview;

use App;

class CarInsurance extends MoneyviewServiceRequest
{
    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Car\ProductListClient::class,
            'description' => 'Request list of products',
        ],
        'list'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Car\ChoiceListClient::class,
            'description' => 'Request list of choice data for certain inputs',
        ],
        'premium'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Car\PremiumClient::class,
            'description' => 'Requests premium by various arguments',
        ],
        'additional_coverages'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Car\AdditionalCoveragesClient::class,
            'description' => 'Request additional car insurance coverages prices.',
        ],
        'policy'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Car\PolicyClient::class,
            'description' => 'Requests policy by company and coverage',
        ],
        'models'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Car\CarModelsClient::class,
            'description' => 'Get car model names by brand, fueltype and construction date',
        ],
        'types'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Car\CarTypesClient::class,
            'description' => 'Get car types and detailed info by brand, fueltype, construction date and model type name',
        ],
    ];

    protected $filterMapping = [
    ];
}