<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\Schipholparking\Parking;
use App\Resources\Schipholparking\SchipholparkingAbstractRequest;

class Prices extends SchipholparkingAbstractRequest
{
    protected $cacheDays = 1;

    protected $defaultMethodName = 'NewBookingRequestParkingAvailability';

    protected function getParamDefaults()
    {
        // All params must be present, with an empty string if not used.
        return [
            'CustomerCode' => $this->customerCode,
            'StartDate' => '',
            'EndDate' => '',
            'ArrivalTimeHHMM' => '',
            'DepartureTimeHHMM' => '',
            'AirportCode' => '',
            'PromotionalCode' => '',
            'LanguageCode' => $this->defaultLanguageCode,
            'AgentCode' => $this->agentCode,
            'ProductCode' => '',
            'CategoryCode' => '',
            'AgentPassword' => '',
            'Terminal' => '',
            'InternationalDomestic' => '',
        ];
    }

    public function setParams(Array $params)
    {
        $params = array_merge($this->getParamDefaults(), $params);

        list($params['StartDate'], $params['ArrivalTimeHHMM']) = $this->splitDate($params['StartDate']);
        list($params['EndDate'], $params['DepartureTimeHHMM']) = $this->splitDate($params['EndDate']);

        // Currently the only available airport is Schiphol, so we default to that to get all.
        if (empty($params['AirportCode']))
            $params['AirportCode'] = Parking::AIRPORTCODE_SCHIPHOL;

        return parent::setParams($params);
    }

    public function getResult()
    {
        $prices = [];

        foreach ($this->getItemArray($this->result['items'], 'availcarpark') as $key => $availability)
        {
            $price = [];

            $price[Parking::NAME] = $availability['name'];
            $price[Parking::LOCATION_ID] = $this->params['AirportCode'] .'|'. trim($availability['carparkcode']) . '|' . trim($availability['productcode']);
            $price[Parking::PRICE_ACTUAL] = (float)$availability['price'];
            $price[Parking::IS_UNAVAILABLE] = !$availability['bookable'];
            $price[Parking::DESCRIPTION] = $availability['bulletpointone'] . "\n" . $availability['bulletpointtwo'];

            unset($availability['bookable'], $availability['price'], $availability['name']);
            $price['@unmapped'] = $availability;

            $prices[] = $price;
        }

        return $prices;
    }
}