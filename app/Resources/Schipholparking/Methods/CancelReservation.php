<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\Schipholparking\SchipholparkingAbstractRequest;


class CancelReservation extends SchipholparkingAbstractRequest
{
    protected $cacheDays = false;
    protected $defaultMethodName = 'ManageBookingCancelParkingBooking';

    protected function getParamDefaults()
    {
        return [
            'CustomerCode' => $this->customerCode,
            'EmailAddress' => 'noreply@parcompare.com',  // "Required", but we do not store it
            'BookingNumber' => '', // Required
            'PostalCode' => 'none',    // Required, defaulted to 'none' in our CreateReservation
            'CancellationReason' => '',
            'AgentCode' => $this->agentCode,
            'LanguageCode' => $this->defaultLanguageCode,
            'AgentPassword' => '',
        ];
    }

    public function setParams(Array $params)
    {
        if (isset($params['CustomerEmail']))
            $params['EmailAddress'] = $params['CustomerEmail'];

        $params = array_merge($this->getParamDefaults(), $params);

        return parent::setParams($params);
    }

    public function getResult()
    {
        $rawResult = parent::getResult();

        if ($rawResult['bookingcancelled'] != true && !$this->getErrorString())
            $this->setErrorString('Reservation could not be cancelled.');

        return ['@unmapped' => $rawResult];
    }
}