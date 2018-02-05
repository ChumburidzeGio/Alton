<?php
namespace App\Resources\Schipholparking\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Schipholparking\Parking;
use App\Resources\Schipholparking\SchipholparkingAbstractRequest;

class CreateReservation extends SchipholparkingAbstractRequest
{
    protected $cacheDays = false;
    protected $defaultMethodName = 'NewBookingMakeParkingBooking';

    protected function getParamDefaults()
    {
        return [
            'CustomerCode' => $this->customerCode,
            'StartDate' => '',
            'EndDate' => '',
            'ArrivalTimeHHMM' => '',
            'DepartureTimeHHMM' => '',
            'CarParkCode' => '',
            'ProductCode' => '',
            'LanguageCode' => $this->defaultLanguageCode,
            'AgentCode' => $this->agentCode,
            'AgentPassword' => '',
            'KeyNumber' => 'staticKey',
            'PromotionalCode' => '',
            'CustomerCompany' => '',
            'CustomerTitle' => '',
            'CustomerFirstName' => '',
            'CustomerSurname' => '',
            'CustomerAddress1' => '',
            'CustomerAddress2' => '',
            'CustomerAddress3' => '',
            'CustomerTown' => '',
            'CustomerCounty' => '',
            'CustomerPostalZipCode' => 'none', // This field is not required to be set to call this method, but it is required to be set, to be able to retrieve it later via RetrieveBooking.
            'CustomerCountry' => '',
            'CustomerTelephoneNumber' => '',
            'CustomerMobileNumber' => '',
            'CustomerEmail' => '',
            'VehicleRegistration' => '',
            'VehicleMake' => '',
            'VehicleModel' => '',
            'VehicleColor' => '',
            'CarParkAccessNumber' => '',
            'CarParkAccessCardType' => 'N',
            'AffiliateBookingReference' => '',
            'SubscribeToNewsletter' => '',
            'SubscribeToSpecialOffers' => '',
            'PaymentCardNumber' => '',
            'PaymentCardExpiryMM' => '',
            'PaymentCardExpiryYY' => '',
            'PaymentCardStartMM' => '',
            'PaymentCardStartYY' => '',
            'PaymentCardIssueNumber' => '',
            'PaymentCardNameOnCard' => '',
            'PaymentCardTypeDescription' => '',
            'PaymentCardSecurityCode' => '',
            'AddsOns' => '',
            'SendEmailConfirmation' => 'N', // Defaults to 'N', but lets be sure.
            'DestinationAirportCode' => '',
            'FrequentFlyerNumber' => '',
            'PNR' => '',
            'outboundflightno' => 'XX9999',
            'outboundflightdate' => '',
            'outboundflighttime' => '',
            'inboundflightno' => '',
            'inboundflightdate' => '',
            'inboundflighttime' => '',
            'airline' => '',
            'drivertitle' => 'Mx',
            'drivername' => '',
            'driversurname' => '',
            'differentdriver' => 'N',
            'differentdrivertitle' => '',
            'differentdrivername' => '',
            'differentdriversurname' => '',
            'collectiondate' => '',
            'collectiontime' => '',
        ];
    }

    public function setParams(Array $params)
    {
        $params = array_merge($this->getParamDefaults(), $params);

        list($params['StartDate'], $params['ArrivalTimeHHMM']) = $this->splitDate($params['StartDate']);
        list($params['EndDate'], $params['DepartureTimeHHMM']) = $this->splitDate($params['EndDate']);

        if (isset($params['AddsOns']))
            $params['AddsOns'] = $this->createAddonsXml($params['AddsOns']);

        if (isset($params[Parking::LOCATION_ID]))
        {
            $locationInfo = $this->splitLocationId($params[Parking::LOCATION_ID]);
            $params['CarParkCode'] = $locationInfo['carparkCode'];
            $params['ProductCode'] = $locationInfo['productCode'];
        }

        if (isset($params[ResourceInterface::EXTERNAL_ID]))
            $params['AffiliateBookingReference'] = $params[ResourceInterface::EXTERNAL_ID];

        // If Valet, then use VehicleRegistration and look up brand info
        if (isset($params['ProductCode']) && in_array($params['ProductCode'], $this->valetProducts))
        {
            if (isset($params['CarParkAccessNumber']))
                $params['VehicleRegistration'] = $params['CarParkAccessNumber'];
            $params['CarParkAccessNumber'] = '';

            $carData = $this->internalRequest('rdw', 'licenseplate', [ResourceInterface::LICENSEPLATE => $params['VehicleRegistration']], true);
            if ($this->resultHasError($carData))
                $this->addErrorMessage('licenseplate', 'unknown-licenseplate-parking', 'Onbekend kenteken.');

            if (isset($carData[ResourceInterface::BRAND_NAME]))
                $params['VehicleMake'] = $carData[ResourceInterface::BRAND_NAME];
            if (isset($carData[ResourceInterface::MODEL_NAME]))
                $params['VehicleModel'] = $carData[ResourceInterface::MODEL_NAME];
            if (isset($carData[ResourceInterface::COLOR]))
                $params['VehicleColor'] = $carData[ResourceInterface::COLOR];
            if (isset($params['StartDate']))
                $params['outboundflightdate'] = $params['StartDate'];
            if (isset($params['ArrivalTimeHHMM']))
                $params['outboundflighttime'] = $params['ArrivalTimeHHMM'];
            if (isset($params['EndDate']))
                $params['inboundflightdate'] = $params['EndDate'];
            if (isset($params['DepartureTimeHHMM']))
                $params['inboundflighttime'] = $params['DepartureTimeHHMM'];
            if (isset($params['CustomerFirstName']))
                $params['drivername'] = $params['CustomerFirstName'];
            if (isset($params['CustomerSurname']))
                $params['driversurname'] = $params['CustomerSurname'];
        }

        // We set this error ourselves, because the Schiphol web API itself will give a misleading message if this is missing.
        if ($params['CustomerSurname'] === '')
            $this->setErrorString('Input `CustomerSurname` is required.');

        return parent::setParams($params);
    }

    public function getResult()
    {
        $rawResult = parent::getResult();

        $reservation = [];
        if (isset($rawResult['bookingref']))
            $reservation[ResourceInterface::ORDER_ID] = $rawResult['bookingref'];
        // This price is EXCLUDING Options
        if (isset($rawResult['price']))
            $reservation[ResourceInterface::PRICE_DEFAULT] = $rawResult['price'];

        // We copy the order ID as reservation code
        if (isset($rawResult['bookingref']))
            $reservation[ResourceInterface::RESERVATION_CODE] = $rawResult['bookingref'];

        // Barcode image not used. Ditch the big clump of data.
        unset($rawResult['barcodefile'], $rawResult['price']);

        $reservation['@unmapped'] = $rawResult;

        return $reservation;
    }
}