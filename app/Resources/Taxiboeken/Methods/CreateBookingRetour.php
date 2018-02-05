<?php


namespace App\Resources\Taxiboeken\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxiboeken\TaxiboekenAbstractRequest;
use App\Resources\Taxitender\TaxitenderAbstractRequest;

class CreateBookingRetour extends TaxiboekenAbstractRequest
{
    protected $externalToResultMapping = [
        'reservation_key'          => ResourceInterface::RESERVATION_KEY,
        'bookingID'                => ResourceInterface::BOOKING_ID,
        'bookingStatus'            => ResourceInterface::BOOKING_STATUS,
        'searchQueryID'            => ResourceInterface::SEARCH_QUERY_ID,
        'searchQueryResultID'      => ResourceInterface::SEARCH_QUERY_RESULT_ID,
        'vehicleTitle'             => ResourceInterface::TITLE,
        'vehicleExample'           => ResourceInterface::DESCRIPTION,
        'vehicleImage'             => ResourceInterface::IMAGE,
        'vehicleCategory'          => ResourceInterface::CATEGORY,
        'priceInclVat'             => ResourceInterface::PRICE_ACTUAL,
        'distance'                 => ResourceInterface::DISTANCE,
        'duration'                 => ResourceInterface::TIME,
        'taxiTenderLogo'           => ResourceInterface::BRAND_LOGO,
        'vehiclePassengerCapacity' => ResourceInterface::PASSENGERS_CAPACITY,
    ];

    public function __construct()
    {
        parent::__construct('createBooking');
    }

    public function getResult()
    {
        $this->result[$this->resultKeyname] += $this->result[$this->resultKeyname]['selectedSearchQueryResult'];
        unset($this->result[$this->resultKeyname]['selectedSearchQueryResult']);

        return parent::getResult();
    }

    public function setParams(array $params)
    {
        $params['paymentMethod'] = array_get($params, 'paymentMethod', 'link');

        parent::setParams($params);
    }
}