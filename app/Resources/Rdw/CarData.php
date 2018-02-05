<?php

namespace App\Resources\Rdw;

use App\Resources\AbstractServiceRequest;

class CarData extends AbstractServiceRequest
{
    protected $methodMapping = [
        'licenseplate'          => [
            'class'       => \App\Resources\Rdw\Methods\CarDataByLicenseplate::class,
            'description' => 'Get basic car data from a license plate.',
        ],
        'licenseplate.fuel'          => [
            'class'       => \App\Resources\Rdw\Methods\FuelDataByLicenseplate::class,
            'description' => 'Get detailed engine and fuel data from license plate.',
        ],
    ];
}
