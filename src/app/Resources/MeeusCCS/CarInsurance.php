<?php

namespace App\Resources\MeeusCCS;

use App\Resources\AbstractServiceRequest;

class CarInsurance extends AbstractServiceRequest
{
    protected $methodMapping = [
        'get_product_info'          => [
            'class'       => \App\Resources\MeeusCCS\Methods\GetProductInfo::class,
            'description' => 'Get information about a single product. ("IniteerService")',
        ],
        'premium'          => [
            'class'       => \App\Resources\MeeusCCS\Methods\CalculatePremium::class,
            'description' => 'Get premium info for one product. ("PremieberekenenKortMR")',
        ],
        'licenseplate'          => [
            'class'       => \App\Resources\MeeusCCS\Methods\CarDataByLicenseplate::class,
            'description' => 'Get car info via ISA',
        ],
        'ccsdata'          => [
            'class'       => \App\Resources\MeeusCCS\Methods\CreateCcsData::class,
            'description' => 'Create CCS prefil data',
        ],
    ];
}
