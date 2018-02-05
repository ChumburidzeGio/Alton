<?php

namespace App\Resources\Isa;

use App\Resources\AbstractServiceRequest;

class CarData extends AbstractServiceRequest
{
    protected $methodMapping = [
        'licenseplate'          => [
            'class'       => \App\Resources\Isa\Methods\CarDataByLicenseplate::class,
            'description' => 'Get ISA (ABZ Audascan v8) car data from a license plate.',
        ],
    ];
}
