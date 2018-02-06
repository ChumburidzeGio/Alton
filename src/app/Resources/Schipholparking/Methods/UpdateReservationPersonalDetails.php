<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\Schipholparking\Parking;
use App\Resources\Schipholparking\SchipholparkingAbstractRequest;

class UpdateReservationPersonalDetails extends SchipholparkingAbstractRequest
{
    protected $defaultMethodName = 'ManageBookingChangePersonalDetails';

    protected $cacheDays = false;
    public $resource2Request = true;

    protected function getParamDefaults()
    {
        return [
            'CustomerCode' => $this->customerCode,
            'ClientEmail' => '',
            'PostCode' => 'none', // This field is not required to be set to call this method, but it is required to be set, to be able to retrieve it later via RetrieveBooking.
            'BookingRef' => '',
            'LanguageCode' => $this->defaultLanguageCode,

            'Title' => '',
            'Initial' => '',                // Not in use
            'CustomerSurname' => '',
            'addressIn1' => '',
            'addressIn2' => '',             // Not in use
            'addressIn3' => '',             // Not in use
            'county' => '',
            'country' => '',
            'telephone' => '',
            'daytimetlfn' => '',            // Not in use
            'fax' => '',                    // Not in use
            'shareinfo' => '',              // Not in use
            'Noofpreviousflights' => '',    // Not in use
            'CustomerFirstName' => '',
            'urlid' => '',                  // Not in use
            'LanguageCode' => '',
            'mobile' => '',
            'AgentCode' => $this->agentCode,
            'agentname' => '',              // Not in use
        ];
    }

    public function setParams(Array $params)
    {
        $inputParams = $params;

        if (isset($params[Parking::LOCATION_ID]))
        {
            $locationInfo = $this->splitLocationId($params[Parking::LOCATION_ID]);
            $params['CarParkCode'] = $locationInfo['carparkCode'];
            $params['ProductCode'] = $locationInfo['productCode'];
        }

        // Map 'UpdateReservation' inputs to this methods' slightly different params
        if (!empty($inputParams['BookingNumber']))
            $params['BookingRef'] = $inputParams['BookingNumber'];
        if (!empty($inputParams['CustomerTelephoneNumber']))
            $params['telephone'] = $inputParams['CustomerTelephoneNumber'];
        if (!empty($inputParams['CustomerEmail']))
            $params['ClientEmail'] = $inputParams['CustomerEmail'];

        $params = array_merge($this->getParamDefaults(), $params);

        return parent::setParams($params);
    }
}