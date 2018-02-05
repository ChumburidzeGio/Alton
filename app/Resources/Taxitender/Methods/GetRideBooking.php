<?php


namespace App\Resources\Taxitender\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;

class GetRideBooking extends TaxitenderAbstractRequest
{
    protected $inputToExternalMapping = [
        ResourceInterface::BOOKING_ID => 'bookingID',
    ];
    protected $externalToResultMapping = [
        'bookingID'                => ResourceInterface::BOOKING_ID,
        'bookingStatus'            => ResourceInterface::BOOKING_STATUS,
        'selectedSearchQueryResult.searchQueryID'            => ResourceInterface::SEARCH_QUERY_ID,
        'selectedSearchQueryResult.searchQueryResultID'      => ResourceInterface::SEARCH_QUERY_RESULT_ID,
        'selectedSearchQueryResult.vehicleTitle'             => ResourceInterface::TITLE,
        'selectedSearchQueryResult.vehicleExample'           => ResourceInterface::DESCRIPTION,
        'selectedSearchQueryResult.vehicleImage'             => ResourceInterface::IMAGE,
        'selectedSearchQueryResult.vehicleCategory'          => ResourceInterface::CATEGORY,
        'selectedSearchQueryResult.priceInclVat'             => ResourceInterface::PRICE_ACTUAL,
        'selectedSearchQueryResult.distance'                 => ResourceInterface::DISTANCE,
        'selectedSearchQueryResult.duration'                 => ResourceInterface::TIME,
        'selectedSearchQueryResult.taxiTenderLogo'           => ResourceInterface::BRAND_LOGO,
        'selectedSearchQueryResult.vehiclePassengerCapacity' => ResourceInterface::PASSENGERS_CAPACITY,
    ];


    public function __construct()
    {
        parent::__construct('retrieveBookingDetails');
    }
}