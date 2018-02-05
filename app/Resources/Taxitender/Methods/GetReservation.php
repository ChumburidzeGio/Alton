<?php


namespace App\Resources\Taxitender\Methods;


use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;

class GetReservation extends TaxitenderAbstractRequest
{
    protected $inputToExternalMapping = false;
    protected $externalToResultMapping = false;
    protected $resultKeyname = false;

    public function executeFunction()
    {
        $bookingIds = explode('|', array_get($this->params, ResourceInterface::ORDER_ID));

        $results = [];
        foreach ($bookingIds as $bookingId) {
            $results[] = ResourceHelper::callResource2('get_ride_booking.taxitender', [
                ResourceInterface::BOOKING_ID => $bookingId,
                ResourceInterface::CANCELLED_BY => 'affiliate',
            ]);
        }

        $this->result = [
            ResourceInterface::DATA => $results,
        ];
    }
}