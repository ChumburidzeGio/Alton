<?php

namespace App\Resources\Inshared;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractServiceRequest;

class CarInsurance extends AbstractServiceRequest
{
    // Komparu-to-external mapping
    protected $filterKeyMapping = [
    ];

    // External-to-Komparu mapping
    protected $fieldMapping = [
        'polisnummer' => ResourceInterface::CONTRACT_ID,
    ];


    protected $methodMapping = [
        'premium'              => [
            'class'       => \App\Resources\Inshared\Methods\CalculatePremium::class,
            'description' => 'Get car insurance premium price.'
        ],
        'contract'              => [
            'class'       => \App\Resources\Inshared\Methods\CreateContract::class,
            'description' => 'Create a car insurance contract.'
        ],
    ];

}
