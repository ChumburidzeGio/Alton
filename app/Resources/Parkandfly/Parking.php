<?php

namespace App\Resources\Parkandfly;

use App\Resources\AbstractServiceRequest;

class Parking extends AbstractServiceRequest
{
    protected $methodMapping = [
        'locations'          => [
            'class'       => \App\Resources\Parkandfly\Methods\Locations::class,
            'description' => 'Get locations'
        ],
        'options'            => [
            'class'       => \App\Resources\Parkandfly\Methods\Options::class,
            'description' => 'Get options'
        ],
        'parkings'           => [
            'class'       => \App\Resources\Parkandfly\Methods\Parkings::class,
            'description' => 'Get parking companies connected'
        ],
        'price'              => [
            'class'       => \App\Resources\Parkandfly\Methods\Price::class,
            'description' => 'Get parking price for one location'
        ],
        'prices'              => [
            'class'       => \App\Resources\Parkandfly\Methods\Prices::class,
            'description' => 'Get parking prices'
        ],
        'create_reservation' => [
            'class'       => \App\Resources\Parkandfly\Methods\CreateReservation::class,
            'description' => 'Create reservation'
        ],
        'get_reservation' => [
            'class'       => \App\Resources\Parkandfly\Methods\GetReservation::class,
            'description' => 'Get existing reservation'
        ],
        'cancel_reservation' => [
            'class'       => \App\Resources\Parkandfly\Methods\CancelReservation::class,
            'description' => 'Cancel reservation'
        ],
        'update_reservation' => [
            'class'       => \App\Resources\Parkandfly\Methods\UpdateReservation::class,
            'description' => 'Update reservation'
        ],
    ];

}
