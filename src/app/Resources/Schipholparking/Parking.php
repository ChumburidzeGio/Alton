<?php

namespace App\Resources\Schipholparking;

use App\Resources\AbstractServiceRequest;

class Parking extends AbstractServiceRequest
{
    // API specific constants
    const LANGUAGECODE_DUTCH = 'NL';
    const LANGUAGECODE_ENGLISH = 'EN';
    const AIRPORTCODE_SCHIPHOL = 'AMS';

    // Komparu-to-external mapping
    protected $filterKeyMapping = [
        self::ARRIVAL_DATE   => 'StartDate',
        self::DEPARTURE_DATE => 'EndDate',
        // CreateReservation / UpdateReservation
        self::LICENSEPLATE   => 'CarParkAccessNumber',
        self::LAST_NAME      => 'CustomerSurname',
        self::FIRST_NAME     => 'CustomerFirstName',
        self::EMAIL          => 'CustomerEmail',
        self::PHONE          => 'CustomerTelephoneNumber',
        self::AIRPORT_CODE   => 'AirportCode',
        self::RETURN_FLIGHT_NUMBER => 'inboundflightno',
        self::OUTBOUND_FLIGHT_NUMBER => 'outboundflightno',
        self::OPTIONS        => 'AddsOns',
        //self::EXTERNAL_ID    => 'AffiliateBookingReference', // Do not enable - is bugged on current API
        // GetReservation
        self::ORDER_ID       => 'BookingNumber',
        self::POSTAL_CODE    => 'PostalCode',
    ];

    // External-to-Komparu mapping
    protected $fieldMapping = [
        //'bookingref'      => self::ORDER_ID,
        //'addoncode'       => self::ID,
        //'carparkname'     => self::NAME,
    ];


    protected $methodMapping = [
        'locations'          => [
            'class'       => \App\Resources\Schipholparking\Methods\Locations::class,
            'description' => 'Get locations'
        ],
        'options'            => [
            'class'       => \App\Resources\Schipholparking\Methods\Options::class,
            'description' => 'Get options'
        ],
        'parkings'           => [
            'class'       => \App\Resources\Schipholparking\Methods\Parkings::class,
            'description' => 'Get parking companies connected'
        ],
        'price'              => [
            'class'       => \App\Resources\Schipholparking\Methods\Price::class,
            'description' => 'Get parking price for one location'
        ],
        'prices'              => [
            'class'       => \App\Resources\Schipholparking\Methods\Prices::class,
            'description' => 'Get parking prices'
        ],
        'create_reservation' => [
            'class'       => \App\Resources\Schipholparking\Methods\CreateReservation::class,
            'description' => 'Create reservation'
        ],
        'get_reservation' => [
            'class'       => \App\Resources\Schipholparking\Methods\GetReservation::class,
            'description' => 'Get existing reservation'
        ],
        'cancel_reservation' => [
            'class'       => \App\Resources\Schipholparking\Methods\CancelReservation::class,
            'description' => 'Cancel reservation'
        ],
        'update_reservation' => [
            'class'       => \App\Resources\Schipholparking\Methods\UpdateReservation::class,
            'description' => 'Update reservation'
        ],
    ];

}
