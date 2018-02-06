<?php
namespace App\Resources\Parkingpro\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;

class GetReservation extends ParkingproAbstractRequest
{
    protected $cacheDays = false;

    protected $inputToExternalMapping = [
        ResourceInterface::PARKING_ID       => 'parkingId',
    ];
    protected $externalToResultMapping = [
        'id'                => ResourceInterface::ORDER_ID,
        'locationId'        => ResourceInterface::LOCATION_ID,
        'parkingDate'       => ResourceInterface::ARRIVAL_DATE,
        'returnDate'        => ResourceInterface::DEPARTURE_DATE,
        'carLicensePlate'   => ResourceInterface::LICENSEPLATE,
        'lastName'          => ResourceInterface::LAST_NAME,
        'firstName'         => ResourceInterface::FIRST_NAME,
        'email'             => ResourceInterface::EMAIL,
        'phone'             => ResourceInterface::PHONE,
        'appliedOptions'    => ResourceInterface::OPTIONS,
        'reservationCode'   => ResourceInterface::RESERVATION_CODE,
        'numberOfPersons'   => ResourceInterface::NUMBER_OF_PERSONS,
        'departureFlightNumber' => ResourceInterface::RETURN_FLIGHT_NUMBER,
        'externalId'        => ResourceInterface::EXTERNAL_ID,
        'internalRemarks'   => ResourceInterface::INTERNAL_REMARKS,
        'customerRemarks'   => ResourceInterface::CUSTOMER_REMARKS,
        'totalAmountWithTax' => ResourceInterface::PAYMENT_AMOUNT,
    ];
    protected $resultTransformations = [
        ResourceInterface::ARRIVAL_DATE => 'formatResultDateTime',
        ResourceInterface::DEPARTURE_DATE => 'formatResultDateTime',
    ];

    public function __construct()
    {
        parent::__construct('reservation/{order_id}');
    }
}