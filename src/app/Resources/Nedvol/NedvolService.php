<?php

namespace App\Resources\Nedvol;

use App\Resources\AbstractServiceRequest;

class NedvolService extends AbstractServiceRequest
{

    protected $methodMapping = [
        'carinsurancecontract'     => [
            'class'       => \App\Resources\Nedvol\Methods\CarInsurance::class,
            'description' => 'Create Carinsurance Contract'
        ],
    ];

}


?>