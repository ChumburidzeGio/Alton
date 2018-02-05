<?php


namespace App\Resources\Taxitender\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;

class CancelRideBooking extends TaxitenderAbstractRequest
{
    protected $inputToExternalMapping = [
        ResourceInterface::BOOKING_ID => 'bookingID',
        ResourceInterface::CANCELLED_BY => 'cancelledBy',
        ResourceInterface::CANCELLATION_REASON => 'cancellationReason',
    ];
    protected $externalToResultMapping = [];


    public function __construct()
    {
        parent::__construct('cancelBooking');
    }
}