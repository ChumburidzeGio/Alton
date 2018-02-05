<?php

namespace App\Resources\Parkingci;


use App\Resources\AbstractServiceRequest;

class Parking extends AbstractServiceRequest
{
    protected $methodMapping = [
        'locations'          => [
            'class'       => \App\Resources\Parkingci\Methods\Locations::class,
            'description' => 'Get locations'
        ],
        'airports'          => [
            'class'       => \App\Resources\Parkingci\Methods\Airports::class,
            'description' => 'Get airports'
        ],
        'options'          => [
            'class'       => \App\Resources\Parkingci\Methods\Options::class,
            'description' => 'Get parking options'
        ],
        'services'          => [
            'class'       => \App\Resources\Parkingci\Methods\Services::class,
            'description' => 'Get parking services'
        ],
        'price'              => [
            'class'       => \App\Resources\Parkingci\Methods\Price::class,
            'description' => 'Get parking prices for a single location'
        ],
        'prices'              => [
            'class'       => \App\Resources\Parkingci\Methods\Prices::class,
            'description' => 'Get parking prices for multiple locations'
        ],
        'create_reservation' => [
            'class'       => \App\Resources\Parkingci\Methods\CreateReservation::class,
            'description' => 'Create a parking reservation.'
        ],
        'get_reservation' => [
            'class'       => \App\Resources\Parkingci\Methods\GetReservation::class,
            'description' => 'Get a parking reservation.'
        ],
        'cancel_reservation' => [
            'class'       => \App\Resources\Parkingci\Methods\CancelReservation::class,
            'description' => 'Cancel a parking reservation.'
        ],
        'update_reservation' => [
            'class'       => \App\Resources\Parkingci\Methods\UpdateReservation::class,
            'description' => 'Update a parking reservation.'
        ],
        'notify_payment' => [
            'class'       => \App\Resources\Parkingci\Methods\NotifyPayment::class,
            'description' => 'Notify service that a payment is complete.'
        ],
    ];
}