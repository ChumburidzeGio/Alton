<?php

namespace App\Resources\Taxiboeken;

use App\Resources\AbstractServiceRequest;

class Taxiboeken extends AbstractServiceRequest
{
    protected $methodMapping = [
        'single_ride_prices' => [
            'class'       => \App\Resources\Taxiboeken\Methods\SingleRidePrices::class,
            'description' => 'Get prices for a single trip'
        ],
        'prices' => [
            'class'       => \App\Resources\Taxiboeken\Methods\Prices::class,
            'description' => 'Get prices'
        ],
    ];
}