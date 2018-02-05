<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\Schipholparking\Parking;
use App\Resources\Schipholparking\SchipholparkingAbstractRequest;

class Options extends SchipholparkingAbstractRequest
{
    protected $defaultMethodName = 'NewBookingRequestAddOns';

    protected $cacheDays = false;

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
            'KeyNumber' => 'staticKey',
            'AgentPassword' => '',
        ];
    }

    public function setParams(Array $params)
    {
        $params = array_merge($this->getParamDefaults(), $params);

        list($params['StartDate'], $params['ArrivalTimeHHMM']) = $this->splitDate($params['StartDate']);
        list($params['EndDate'], $params['DepartureTimeHHMM']) = $this->splitDate($params['EndDate']);

        if (isset($params[Parking::LOCATION_ID]))
        {
            $info = $this->splitLocationId($params[Parking::LOCATION_ID]);
            $params['CarParkCode'] = $info['carparkCode'];
            // We do NOT pass the productCode to ProductCode - they are apparently different fields.
        }

        return parent::setParams($params);
    }

    public function getResult()
    {
        // Clean up the mess a bit
        $options = [];

        foreach ($this->getItemArray($this->result['addons'], 'availaddon') as $key => $rawOption)
        {
            $option[Parking::ID] = trim($rawOption['addoncode']); // Sometimes has trailing spaces, which you should ignore
            $option[Parking::NAME] = $rawOption['name'];
            $option[Parking::DESCRIPTION] = $rawOption['bulletpointone'] ."\n". (!empty($rawOption['bulletpointtwo']) ? $rawOption['bulletpointtwo'] ."\n" : '');
            $option[Parking::PRICE_ACTUAL] = $rawOption['priceperitem'];
            $option[Parking::IS_UNAVAILABLE] = !$rawOption['bookable'];

            unset($rawOption['name'], $rawOption['priceperitem']);
            $option['@unmapped'] = $rawOption;

            $options[] = $option;
        }

        return $options;
    }
}