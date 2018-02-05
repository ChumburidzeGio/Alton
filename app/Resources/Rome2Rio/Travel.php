<?php

namespace App\Resources\Rome2Rio\Travel;


use App\Resources\AbstractServiceRequest;

class Travel extends AbstractServiceRequest
{
    protected $methodMapping = [
        'coordinates'          => [
            'class'       => \App\Resources\Rome2Rio\Travel\Methods\Coordinates::class,
            'description' => 'Get coordinates for a given address'
        ],
        'routes'          => [
            'class'       => \App\Resources\Rome2Rio\Travel\Methods\Routes::class,
            'description' => 'Get routes for a given place or coordinate pair'
        ],
    ];
}