<?php

namespace App\Resources\Quickparking;

use App\Resources\AbstractServiceRequest;

class Parking extends AbstractServiceRequest
{
    // Komparu-to-external mapping
    protected $filterKeyMapping = [
        self::ARRIVAL_DATE   => 'incomingDate',
        self::DEPARTURE_DATE => 'outgoingDate',
        self::OPTIONS        => 'serviceids',
    ];

    // External-to-Komparu mapping
    protected $fieldMapping = [
        'labelid'         => self::LOCATION_ID,
        'labelID'         => self::LOCATION_ID,
        'labelName'       => self::NAME,
        'IATAcode'        => self::AIRPORT_CODE,
        'price'           => self::PRICE_ACTUAL,
        'price_withoutservices' => self::PRICE_DEFAULT,
    ];

    protected $methodMapping = [
        'locations'          => [
            'class'       => \App\Resources\Quickparking\Methods\Locations::class,
            'description' => 'Get locations'
        ],
        'options'            => [
            'class'       => \App\Resources\Quickparking\Methods\Options::class,
            'description' => 'Get options'
        ],
        'parkings'           => [
            'class'       => \App\Resources\Quickparking\Methods\Parkings::class,
            'description' => 'Get parking companies connected'
        ],
        'price'              => [
            'class'       => \App\Resources\Quickparking\Methods\Price::class,
            'description' => 'Get parking price for one location'
        ],
        'prices'              => [
            'class'       => \App\Resources\Quickparking\Methods\Prices::class,
            'description' => 'Get parking prices'
        ],
/*        'create_reservation' => [
            'class'       => \App\Resources\Quickparking\Methods\CreateReservation::class,
            'description' => 'Create reservation'
        ],
        'get_reservation' => [
            'class'       => \App\Resources\Quickparking\Methods\GetReservation::class,
            'description' => 'Get existing reservation'
        ],
        'cancel_reservation' => [
            'class'       => \App\Resources\Quickparking\Methods\CancelReservation::class,
            'description' => 'Cancel reservation'
        ],
        'update_reservation' => [
            'class'       => \App\Resources\Quickparking\Methods\UpdateReservation::class,
            'description' => 'Update reservation'
        ],
*/
    ];

}
