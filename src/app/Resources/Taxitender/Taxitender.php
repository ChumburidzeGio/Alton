<?php

namespace App\Resources\Taxitender;

use App\Resources\AbstractServiceRequest;

class Taxitender extends AbstractServiceRequest
{
    protected $methodMapping = [
        'testAuthentication' => [
            'class'       => \App\Resources\Taxitender\Methods\TestAuthentication::class,
            'description' => 'Authentication test'
        ],
        'retrieve_service_locations' => [
            'class'       => \App\Resources\Taxitender\Methods\RetrieveServiceLocations::class,
            'description' => 'Gets the list of service locations (mainly airports)'
        ],
        'find_bookable_rides' => [
            'class'       => \App\Resources\Taxitender\Methods\FindBookableRides::class,
            'description' => 'Finds bookable taxi rides'
        ],
        'create_ride_booking' => [
            'class'       => \App\Resources\Taxitender\Methods\CreateRideBooking::class,
            'description' => 'Creates booking for one ride'
        ],
        'cancel_ride_booking' => [
            'class'       => \App\Resources\Taxitender\Methods\CancelRideBooking::class,
            'description' => 'Cancels booking for one ride'
        ],
        'get_ride_booking' => [
            'class'       => \App\Resources\Taxitender\Methods\GetRideBooking::class,
            'description' => 'Get booking for one ride'
        ],

        'create_reservation' => [
            'class'       => \App\Resources\Taxitender\Methods\CreateReservation::class,
            'description' => 'Creates reservation for a retour or single ride.'
        ],
        'cancel_reservation' => [
            'class'       => \App\Resources\Taxitender\Methods\CancelReservation::class,
            'description' => 'Cancels a full retour or single reservation.'
        ],
        'get_reservation' => [
            'class'       => \App\Resources\Taxitender\Methods\GetReservation::class,
            'description' => 'Get a full retour or single reservation.'
        ],
        'prices' => [
            'class'       => \App\Resources\Taxitender\Methods\Prices::class,
            'description' => 'Finds bookable taxi rides (there and back again in parallel)'
        ],
    ];
}