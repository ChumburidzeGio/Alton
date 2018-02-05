<?php


namespace App\Resources\Taxiboeken\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxiboeken\TaxiboekenAbstractRequest;

class CreateRide extends TaxiboekenAbstractRequest
{
    protected $inputToExternalMapping = [
        ResourceInterface::NUMBER_OF_PERSONS             => 'passengerCount',
        ResourceInterface::CUSTOMER_REMARKS              => 'note',
        ResourceInterface::FLIGHT_NUMBER                 => 'flightNumber',
        ResourceInterface::DEPARTURE_DATE                => 'pickupDate',
        ResourceInterface::EXTERNAL_ID                   => 'foreignId',
        ResourceInterface::ORIGIN_CITY                   => 'departure.city',
        ResourceInterface::ORIGIN_STREET                 => 'departure.streetName',
        ResourceInterface::ORIGIN_POSTAL_CODE            => 'departure.postalCode',
        ResourceInterface::ORIGIN_HOUSE_NUMBER           => 'departure.houseNumber',
        ResourceInterface::ORIGIN_LATITUDE               => 'departure.gps.lat',
        ResourceInterface::ORIGIN_LONGITUDE              => 'departure.gps.lng',
        ResourceInterface::ORIGIN_COUNTRY_CODE           => 'departure.countryCode',
        ResourceInterface::ORIGIN_POINT_OF_INTEREST      => 'departure.internationalAlias',
        ResourceInterface::DESTINATION_CITY              => 'destination.city',
        ResourceInterface::DESTINATION_STREET            => 'destination.streetName',
        ResourceInterface::DESTINATION_POSTAL_CODE       => 'destination.postalCode',
        ResourceInterface::DESTINATION_HOUSE_NUMBER      => 'destination.houseNumber',
        ResourceInterface::DESTINATION_LATITUDE          => 'destination.gps.lat',
        ResourceInterface::DESTINATION_LONGITUDE         => 'destination.gps.lng',
        ResourceInterface::DESTINATION_COUNTRY_CODE      => 'destination.countryCode',
        ResourceInterface::DESTINATION_POINT_OF_INTEREST => 'destination.internationalAlias',
        ResourceInterface::FIRST_NAME                    => 'passenger.fname',
        ResourceInterface::LAST_NAME                     => 'passenger.lname',
        ResourceInterface::EMAIL                         => 'passenger.email',
        ResourceInterface::LANGUAGE                      => 'passenger.language',
        ResourceInterface::PHONE                         => 'passenger.phoneNumber',
    ];
    protected $externalToResultMapping = [
        'id'                       => ResourceInterface::BOOKING_ID,
        'status'                   => ResourceInterface::BOOKING_STATUS,
        'requestedData'            => ResourceInterface::CREATION_DATE,
        'price.total'              => ResourceInterface::PRICE_ACTUAL,
    ];
    protected $resultTransformations = [
        ResourceInterface::PRICE_ACTUAL => 'priceToDecimal',
    ];

    public function __construct()
    {
        parent::__construct('rides', self::METHOD_POST);
    }
}