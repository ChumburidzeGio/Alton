<?php

namespace App\Resources\Paston;

use App\Resources\AbstractServiceRequest;

class CarInsurance extends AbstractServiceRequest
{
    protected $methodMapping = [
        'premium'              => [
            'class'       => \App\Resources\Paston\Methods\CalculatePremium::class,
            'description' => 'Get car insurance premium price.',
        ],
        'ccsdata'              => [
            'class'       => \App\Resources\Paston\Methods\CreateCcsData::class,
            'description' => 'Create a CCS xml prefill xml document, and return other CCS prefill data.',
        ],
    ];
}
