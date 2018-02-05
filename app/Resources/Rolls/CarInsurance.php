<?php

namespace App\Resources\Rolls;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class CarInsurance extends RollsServiceRequest
{
    protected $methodMapping = [
        'licenseplate'   => [
            'class'       => \App\Resources\Rolls\Methods\Impl\CarByLicenseplate::class,
            'description' => 'Request auto model by licenseplate. (Will use `licenseplate_legacy` if called from Resource1, otherwise `licenseplate_basic`.)',
        ],
        'licenseplate_basic'   => [
            'class'       => \App\Resources\Rolls\Methods\Impl\CarByLicenseplate::class,
            'description' => 'Request basic auto model by licenseplate via Rolls REST API.',
        ],
        'licenseplate_premium'   => [
            'class'       => \App\Resources\Rolls\Methods\Impl\CarByLicenseplatePremium::class,
            'description' => 'Request extended auto model by licenseplate via Rolls REST API.',
        ],
        'licenseplate_rollscache'   => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoBijKentekenViaPremieClient::class,
            'description' => 'Request auto data by licenseplate, which was cached on a Rolls Premie call.',
        ],
        'licenseplate_legacy'   => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoBijKentekenClient::class,
            'description' => 'Legacy client, DO NOT USE'
        ],
        'premium'        => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoPremieBerekenClient::class,
            'description' => 'Request premiums by model, coverage, milage and personal details. Either the licence plate or construction date and type_id is required. If license plate is entered without type_id, first type in list will be used'
        ],
        'premium_legacy'        => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoPremieBerekenClientLegacy::class,
            'description' => 'Legacy client, DO NOT USE'
        ],
        'models'         => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoModellenLijstClient::class,
            'description' => 'Request auto model by brand_id, year and month'
        ],
        'products'       => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoProductenLijstClient::class,
            'description' => 'Request list of products'
        ],
        'types'          => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoTypenLijstClient::class,
            'description' => 'Request of types based on model_id, year and month'
        ],
        'brands'         => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoMerkenLijstClient::class,
            'description' => 'Request list of car brands'
        ],
        'cartype'          => [
            'class'       => \App\Resources\Rolls\Methods\Impl\CarByBrandModelTypeID::class,
            'description' => 'Get car information by Rolls Brand, Model and Type ID'
        ],
        'list'           => [
            'class'       => \App\Resources\Rolls\Methods\Impl\KeuzeLijstenClient::class,
            'description' => 'Request list of choosable options'
        ],
        'coveragepolicy' => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoPolisVoorwaardenClient::class,
            'description' => 'List of policy conditions '
        ],
        'policy'         => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AllePolisVoorwaardenClient::class,
            'description' => 'List of all policy conditions ',
        ],
        'extra_products' => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoAanvullendeProductenLijstClient::class,
            'description' => 'List of all policy conditions '
        ],
        'contract' => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoContractClient::class,
            'description' => 'Contract '
        ],
        'damagefreeyears' => [
            'class'       => \App\Resources\Rolls\Methods\Impl\AutoSchadevrijeJaren::class,
            'description' => 'Damage free years '
        ],
        'product_ownrisk'      => [
            'class' => \App\Resources\Rolls\Methods\Impl\AutoEigenRisicosClient::class,
            'description' => 'Try to get all available own risk values of a Rolls product.',
        ],
    ];

    protected $serviceProvider = 'premium.carinsurance';

    public function  __call($method, $args)
    {
        // Provide fallback to old licenseplate resource if calling through the resource1 web interface
        if (!App::runningInConsole() && Request::segment(2) === 'resource' && $method == 'licenseplate')
        {
            $method = 'licenseplate_legacy';
        }

        return parent::__call($method, $args);
    }
}