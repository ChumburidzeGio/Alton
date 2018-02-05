<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\Schipholparking\Parking;
use App\Resources\Schipholparking\SchipholparkingAbstractRequest;

class GetReservation extends SchipholparkingAbstractRequest
{
    protected $cacheDays = false;
    protected $defaultMethodName = 'ManageBookingRetrieveParkingBooking';

    protected function getParamDefaults()
    {
        return [
            'CustomerCode' => $this->customerCode,
            'EmailAddress' => 'noreply@parcompare.com',  // "Required"
            'BookingNumber' => '', // Required
            'PostalCode' => 'none',    // Required, defaulted to 'none' in our CreateReservation
            'CustomerFirstName' => '',
            'CustomerSurname' => '',
            'FrequentFlyerNumber' => '',
            'PNR' => '',
            'InboundFlightNumber' => '',
            'AgentCode' => $this->agentCode,
            'LanguageCode' => $this->defaultLanguageCode,
            'AgentPassword' => '',
            'AirportID' => '',
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

        $reservationData = [];

        if (!isset($rawResult['booking']['bookingdetails']['bookingref'])) {
            $this->setErrorString('Incomplete reservation data received from provider.');
            return;
        }

        $reservationData[Parking::ORDER_ID] = $rawResult['booking']['bookingdetails']['bookingref'];

        $person = $rawResult['booking']['personaldetails'];
        $reservationData[Parking::FIRST_NAME] = $this->removeEmptyArray($person['firstname']);
        $reservationData[Parking::LAST_NAME] = $this->removeEmptyArray($person['surname']);
        $reservationData[Parking::COMPANY_NAME] = isset($person['company']) ? $this->removeEmptyArray($person['company']) : null;
        $reservationData[Parking::PHONE] = $this->removeEmptyArray($person['telephonenumber']);
        $reservationData[Parking::EMAIL] = $this->removeEmptyArray($person['emailaddress']);

        $carpark = $rawResult['booking']['carparking'];
        $reservationData[Parking::ARRIVAL_DATE] = \DateTime::createFromFormat('Y-m-d Hi', substr($carpark['startdate'], 0, 10) .' '. $carpark['arrivaltime'])->format('Y-m-d H:i:s');
        $reservationData[Parking::DEPARTURE_DATE] = \DateTime::createFromFormat('Y-m-d Hi',substr( $carpark['enddate'], 0, 10) .' '. $carpark['departuretime'])->format('Y-m-d H:i:s');
        // TODO: This was returned previously, but the new API version does not :( - may change
        // $reservationData[Parking::LICENSEPLATE] = $this->removeEmptyArray($carpark['vehicleregistration']);

        $optionIds = [];
        $totalAddonsCost = 0;
        foreach ($this->getItemArray($carpark['addons'], 'addon') as $addon)
        {
            if ((int)$addon['quantity'] <= 0)
                continue;

            $optionIds[] = trim($addon['code']) . ($addon['quantity'] > 1 ? ':'. (int)$addon['quantity'] : '');
            $totalAddonsCost += (float)$addon['totalprice'];
        }
        $reservationData[Parking::OPTIONS] = $optionIds;
        $reservationData[Parking::PRICE_OPTIONS] = $totalAddonsCost;

        $reservationData[Parking::PRICE_DEFAULT] = (float)$carpark['price'];

        $reservationData[Parking::PRICE_ACTUAL] = $totalAddonsCost + $reservationData[Parking::PRICE_DEFAULT];

        // TODO: Missing essential data: ProductCode (for location_id) & InboundFlightNumber
        // We cannot construct a location id - no productcode returned. Just a product name. :(
        // $reservationData[Parking::LOCATION_ID] = self::AIRPORTCODE_SCHIPHOL .'|' . $carpark['carparkcode'] .'|'. $NO_PRODUCT_CODE;
        // $reservationData[Parking::RETURN_FLIGHT_NUMBER] = '';

        $reservationData['@unmapped'] = $rawResult['booking'];

        return $reservationData;
    }
}