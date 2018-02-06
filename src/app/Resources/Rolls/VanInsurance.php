<?php

namespace App\Resources\Rolls;

class VanInsurance extends RollsServiceRequest
{

    protected $methodMapping = [
        'licenseplate' => [
            'class'       => \App\Resources\Rolls\Methods\Impl\BestelautoBijKentekenClient::class,
            'description' => 'Request van model by licenseplate'
        ],
        'premium'      => [
            'class'       => \App\Resources\Rolls\Methods\Impl\BestelautoPremieBerekenClient::class,
            'description' => 'Request premiums by model, coverage, milage and personal details. Either the licence plate or construction date and type_id is required. If license plate is entered without type_id, first type in list will be used'
        ],
        'products'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\BestelautoProductenLijstClient::class,
            'description' => 'Request list of products'
        ],
        'brands'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\BestelautoMerkenLijstClient::class,
            'description' => 'Request list of car brands'
        ],
        'models'       => [
            'class'       => \App\Resources\Rolls\Methods\Impl\BestelautoModellenLijstClient::class,
            'description' => 'Request auto model by brand_id, year and month'
        ],
        'types'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\BestelautoTypenLijstClient::class,
            'description' => 'Request of types based on model_id, year and month'
        ],
        'list'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\KeuzeLijstenClient::class,
            'description' => 'Request list of choosable options'
        ],
        'coveragepolicy'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\BestelautoPolisVoorwaardenClient::class,
            'description' => 'List of policy conditions '
        ],
        'policy'       => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AllePolisVoorwaardenClient::class,
            'description' => 'List of all policy conditions '
        ],
        'product_ownrisk'      => [
            'class' => \App\Resources\Rolls\Methods\Impl\BestelautoEigenRisicosClient::class,
            'description' => 'Try to get all available own risk values of a product.',
        ],
    ];

    protected $serviceProvider = 'premium.vaninsurance';

}