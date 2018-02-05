<?php

namespace App\Resources\Google\Geocoding;


use App\Resources\AbstractServiceRequest;

class Geocoding extends AbstractServiceRequest
{
    protected $methodMapping = [
        'coordinates'          => [
            'class'       => \App\Resources\Google\Geocoding\Methods\Coordinates::class,
            'description' => 'Get coordinates for a given address'
        ],
        'search'          => [
            'class'       => \App\Resources\Google\Geocoding\Methods\Search::class,
            'description' => 'Get place id for a given address'
        ],
        'place'          => [
            'class'       => \App\Resources\Google\Geocoding\Methods\Place::class,
            'description' => 'Get coordinates for a given place id'
        ],
    ];
}