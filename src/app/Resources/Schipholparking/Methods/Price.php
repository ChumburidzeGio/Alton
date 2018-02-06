<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\Schipholparking\Parking;

class Price extends Prices
{
    protected $requestedLocationInfo;

    protected $cacheDays = false;

    public function setParams(Array $params)
    {
        if (!isset($params[Parking::LOCATION_ID]))
        {
            $this->setErrorString('Parameter `location_id` is required.');
            return;
        }

        $this->requestedLocationInfo = $this->splitLocationId($params[Parking::LOCATION_ID]);
        $params['AirportCode'] = $this->requestedLocationInfo['airportCode'];
        $params['ProductCode'] = $this->requestedLocationInfo['productCode'];

        parent::setParams($params);
    }

    public function getResult()
    {
        // Clean up the mess a bit
        $prices = [];
        foreach (parent::getResult() as $price)
        {
            // If only want one specific location, skip all others
            if (trim($price['@unmapped']['carparkcode']) != $this->requestedLocationInfo['carparkCode']
              || trim($price['@unmapped']['productcode']) != $this->requestedLocationInfo['productCode'])
                continue;

            $prices[] = $price;
        }

        if (count($prices) == 0) {
            $this->setErrorString('Location `'. implode('|', $this->requestedLocationInfo) .'` could not be found.');
            return;
        }
        if (count($prices) > 1) {
            $this->setErrorString('Error: Multiple locations found for location `'. implode('|', $this->requestedLocationInfo) .'`.');
            return;
        }

        return $prices[0];
    }
}