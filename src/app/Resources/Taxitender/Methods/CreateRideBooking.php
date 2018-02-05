<?php


namespace App\Resources\Taxitender\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;
use Illuminate\Support\Facades\Config;

class CreateRideBooking extends TaxitenderAbstractRequest
{
    protected $inputToExternalMapping = [
        ResourceInterface::SEARCH_QUERY_ID => 'searchQueryID',
        ResourceInterface::SEARCH_QUERY_RESULT_ID => 'searchQueryResultID',
        ResourceInterface::RETURN_FLIGHT_NUMBER => 'flightNumber',
        ResourceInterface::CUSTOMER_REMARKS => 'notesToDriver',
        ResourceInterface::LOYALTY_NUMBER => 'loyaltyNumber',
        ResourceInterface::PAYMENT_METHOD => 'paymentMethod',
        ResourceInterface::FULL_NAME => 'customerFullName',
        ResourceInterface::EMAIL => 'customerEmailAddress',
        ResourceInterface::PHONE_PREFIX => 'customerTelephonePrefix',
        ResourceInterface::PHONE => 'customerTelephoneNumber',
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
        parent::__construct('createBooking');
    }

    protected function getDefaultParams()
    {
        return [
            'paymentMethod'   => ((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.default.paymentMethod')),
        ];
    }
}