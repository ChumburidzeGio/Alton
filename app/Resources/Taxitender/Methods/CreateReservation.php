<?php


namespace App\Resources\Taxitender\Methods;


use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;

class CreateReservation extends TaxitenderAbstractRequest
{
    protected $inputToExternalMapping = false;
    protected $externalToResultMapping = false;
    protected $resultKeyname = false;

    public function executeFunction()
    {
        if (empty($this->params[ResourceInterface::RESERVATION_KEY])) {
            $priceParams = $this->params;
            $priceParams[ResourceInterface::DESTINATION_ARRIVAL_DATE] = array_get($priceParams, ResourceInterface::ARRIVAL_DATE);
            $priceParams[ResourceInterface::DESTINATION_DEPARTURE_DATE] = array_get($priceParams, ResourceInterface::DEPARTURE_DATE);
            $priceParams[ResourceInterface::PASSENGERS] = array_get($priceParams, ResourceInterface::NUMBER_OF_PERSONS);
            $prices = ResourceHelper::callResource2('prices.taxitender', $priceParams);

            if (count($prices) == 0) {
                $this->setErrorString('No taxi available.');
                return;
            }
            $this->params[ResourceInterface::RESERVATION_KEY] = $prices[0][ResourceInterface::RESERVATION_KEY];
        }

        $rides = $this->decodeReservationKey($this->params[ResourceInterface::RESERVATION_KEY]);

        $bookings = [];
        $bookingIds = [];
        foreach ($rides as $ride) {
            $bookings[] = ResourceHelper::callResource2('create_ride_booking.taxitender', array_merge($this->params, $ride));
            $bookingIds[] = array_get(end($bookings), ResourceInterface::BOOKING_ID);
        }

        $this->result = [
            ResourceInterface::ORDER_ID => implode('|', $bookingIds),
            ResourceInterface::DATA => $bookings,
        ];
    }

    private function mergeBookings($to, $from)
    {
        return array_merge([
            ResourceInterface::ORDER_ID => $to[ResourceInterface::SEARCH_QUERY_ID] . '|' . $from[ResourceInterface::SEARCH_QUERY_ID],
            'bookingStatus' => $to[ResourceInterface::BOOKING_STATUS] . '|' . $from[ResourceInterface::BOOKING_STATUS],
        ], TaxitenderAbstractRequest::mergeRides($to, $from));
    }

    public function setParams(array $params)
    {
        $params[ResourceInterface::FULL_NAME] = array_get($params, ResourceInterface::FIRST_NAME) .' '. array_get($params, ResourceInterface::LAST_NAME);

        $params = array_merge($params, $this->parsePhoneNumber(array_get($params, ResourceInterface::PHONE)));

        parent::setParams($params);
    }

    protected function decodeReservationKey($key)
    {
        $unencoded = base64_decode($key);
        list($querySearchId, $querySearchResultId) = explode('_', $unencoded);

        if (str_contains($querySearchId, '|')) {
            $queryIds = explode('|', $querySearchId);
            $queryResultIds = explode('|', $querySearchResultId);
            return [
                [
                    ResourceInterface::SEARCH_QUERY_ID => $queryIds[0],
                    ResourceInterface::SEARCH_QUERY_RESULT_ID => $queryResultIds[0],
                ],
                [
                    ResourceInterface::SEARCH_QUERY_ID => $queryIds[1],
                    ResourceInterface::SEARCH_QUERY_RESULT_ID => $queryResultIds[1],
                ],
            ];
        }

        return [
            [
                ResourceInterface::SEARCH_QUERY_ID => $querySearchId,
                ResourceInterface::SEARCH_QUERY_RESULT_ID => $querySearchResultId,
            ],
        ];
    }

    protected function parsePhoneNumber($phoneNumber)
    {
        //TODO: Find a good phonenumber splitter - WARNING! do not try to write your own. This rabbit hole is deep.

        return [
            ResourceInterface::PHONE_PREFIX => '31',
            ResourceInterface::PHONE => $phoneNumber,
        ];
    }
}