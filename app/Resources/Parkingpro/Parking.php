<?php

namespace App\Resources\Parkingpro;

use App\Resources\AbstractServiceRequest;

class Parking extends AbstractServiceRequest
{
    protected $methodMapping = [
        'locations'          => [
            'class'       => \App\Resources\Parkingpro\Methods\Locations::class,
            'description' => 'Get locations'
        ],
        'options'            => [
            'class'       => \App\Resources\Parkingpro\Methods\Options::class,
            'description' => 'Get options'
        ],
        'parkings'           => [
            'class'       => \App\Resources\Parkingpro\Methods\Parkings::class,
            'description' => 'Get parking companies connected'
        ],
        'price'              => [
            'class'       => \App\Resources\Parkingpro\Methods\Price::class,
            'description' => 'Get parking prices'
        ],
        'prices'              => [
            'class'       => \App\Resources\Parkingpro\Methods\Prices::class,
            'description' => 'Get parking prices for multiple locations (and parkings)'
        ],
        'create_reservation' => [
            'class'       => \App\Resources\Parkingpro\Methods\CreateReservation::class,
            'description' => 'Create reservation'
        ],
        'get_reservation' => [
            'class'       => \App\Resources\Parkingpro\Methods\GetReservation::class,
            'description' => 'Get reservation'
        ],
        'cancel_reservation' => [
            'class'       => \App\Resources\Parkingpro\Methods\CancelReservation::class,
            'description' => 'Cancel reservation'
        ],
        'update_reservation' => [
            'class'       => \App\Resources\Parkingpro\Methods\UpdateReservation::class,
            'description' => 'Update reservation'
        ],
    ];

}
