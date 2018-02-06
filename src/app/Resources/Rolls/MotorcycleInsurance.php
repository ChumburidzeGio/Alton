<?php

namespace App\Resources\Rolls;

use App;

class MotorcycleInsurance extends RollsServiceRequest
{

    protected $methodMapping = [
        'licenseplate' => [
            'class'       => \App\Resources\Rolls\Methods\Impl\MotorBijKentekenClient::class,
            'description' => 'Request auto model by licenseplate'
        ],
        'premium'      => [
            'class'       => \App\Resources\Rolls\Methods\Impl\MotorPremieBerekenClient::class,
            'description' => 'Request premiums by model, coverage, milage and personal details. Either the licence plate or construction date and type_id is required. If license plate is entered without type_id, first type in list will be used'
        ],
        'models'       => [
            'class'       => \App\Resources\Rolls\Methods\Impl\MotorModellenLijstClient::class,
            'description' => 'Request auto model by brand_id, year and month'
        ],
        'products'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\MotorProductenLijstClient::class,
            'description' => 'Request list of products'
        ],
        'types'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\MotorTypenLijstClient::class,
            'description' => 'Request of types based on model_id, year and month'
        ],
        'brands'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\MotorMerkenLijstClient::class,
            'description' => 'Request list of car brands'
        ],
        'list'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\KeuzeLijstenClient::class,
            'description' => 'Request list of choosable options'
        ],
        'coveragepolicy'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\MotorPolisVoorwaardenClient::class,
            'description' => 'List of policy conditions '
        ],
        'policy'       => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AllePolisVoorwaardenClient::class,
            'description' => 'List of all policy conditions '
        ]

    ];

    protected $serviceProvider = 'premium.motorcycleinsurance';
}