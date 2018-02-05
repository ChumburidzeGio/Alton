<?php
namespace App\Resources\Schipholparking\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Schipholparking\Parking;
use App\Resources\Schipholparking\SchipholparkingAbstractRequest;

class UpdateReservationIdentificationDetails extends SchipholparkingAbstractRequest
{
    protected $defaultMethodName = 'ManageBookingChangeIdentificationDetails';
    protected $cacheDays = false;
    public $resource2Request = true;

    protected function getParamDefaults()
    {
        return [
            'CustomerCode' => $this->customerCode,
            'LanguageCode' => $this->defaultLanguageCode,
            'Email' => '',
            'BookingRef' => '',
            'PostCode' => 'none', // This field is not required to be set to call this method, but it is required to be set, to be able to retrieve it later via RetrieveBooking.
            'AgentCode' => $this->agentCode,
            'AgentPassword' => '',
            'IDMethod' => 'N', // We always use Licenseplate ID method
            'AccessCode' => '',
            'VehicleReg' => '',
            'make' => '',
            'model' => '',
            'colour' => '',
            'outboundflightno' => 'XX9999',
            'outboundflightdate' => '',
            'outboundflighttime' => '',
            'inboundflightno' => '',
            'inboundflightdate' => '',
            'inboundflighttime' => '',
            'airline' => '',
            'frequentflyer' => '',
            'drivertitle' => '',
            'drivername' => '',
            'driversurname' => '',
            'differentdriver' => '',
            'collectiondate' => '',
            'collectiontime' => '',
            'urlid' => '',
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
        list($params['StartDate'], $params['ArrivalTimeHHMM']) = $this->splitDate($params['StartDate']);
        list($params['EndDate'], $params['DepartureTimeHHMM']) = $this->splitDate($params['EndDate']);

        // Map 'UpdateReservation' inputs to this methods' slightly different params

        $params = array_merge($this->getParamDefaults(), $params);
        if (!empty($inputParams['BookingNumber']))
            $params['BookingRef'] = $inputParams['BookingNumber'];
        if (!empty($inputParams['inboundflightno']))
            $params['InboundFlightNo'] = $inputParams['inboundflightno'];
        if (!empty($inputParams['outboundflightno']))
            $params['OutboundFlightNo'] = $inputParams['outboundflightno'];
        if (!empty($inputParams['CustomerEmail']))
            $params['Email'] = $inputParams['CustomerEmail'];

        if (!empty($inputParams['CarParkAccessNumber'])) {
            $params['AccessCode'] = $inputParams['CarParkAccessNumber'];
        }

        // If Valet, then use VehicleRegistration and look up brand info
        if (in_array($params['ProductCode'], $this->valetProducts))
        {
            $params['VehicleReg'] = $params['CarParkAccessNumber'];
            $params['CarParkAccessNumber'] = '';

            $carData = $this->internalRequest('rdw', 'licenseplate', [ResourceInterface::LICENSEPLATE => $params['VehicleReg']], true);
            if ($this->resultHasError($carData))
                $this->setErrorString('Onbekend kenteken.');
                //$this->addErrorMessage('licenseplate', 'unknown-licenseplate-parking', 'Onbekend kenteken.');

            if (isset($carData[ResourceInterface::BRAND_NAME]))
                $params['make'] = $carData[ResourceInterface::BRAND_NAME];
            if (isset($carData[ResourceInterface::MODEL_NAME]))
                $params['model'] = $carData[ResourceInterface::MODEL_NAME];
            if (isset($carData[ResourceInterface::COLOR]))
                $params['colour'] = $carData[ResourceInterface::COLOR];
            $params['outboundflightdate'] = $params['StartDate'];
            $params['outboundflighttime'] = $params['ArrivalTimeHHMM'];
            $params['inboundflightdate'] = $params['EndDate'];
            $params['inboundflighttime'] = $params['DepartureTimeHHMM'];
        }

        return parent::setParams($params);
    }
}