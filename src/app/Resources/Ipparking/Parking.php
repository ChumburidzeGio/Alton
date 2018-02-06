<?php

namespace App\Resources\Ipparking;

use App\Resources\AbstractServiceRequest;

class Parking extends AbstractServiceRequest
{
    protected $filterKeyMapping = [
        //banking
        self::LOCATION_ID     => 'LocationId',
            self::ARRIVAL_DATE    => 'Arrival',
        self::DEPARTURE_DATE  => 'Departure',
        self::NUMBER_OF_SPOTS => 'NumberOfSpots',

    ];

    protected $methodMapping = [
        'configuration'     => [
            'class'       => \App\Resources\Ipparking\Methods\Configuration::class,
            'description' => 'Get configuration'
        ],
        'checkavailability' => [
            'class'       => \App\Resources\Ipparking\Methods\CheckAvailability::class,
            'description' => 'Get configuration'
        ]
    ];

}


?>