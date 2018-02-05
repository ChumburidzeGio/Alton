<?php
namespace App\Resources\Parkingci\Methods;

use App\Interfaces\ResourceInterface;

class Price extends Prices
{
    // Overloads from Prices
    protected $inputToExternalMapping = [
        ResourceInterface::ARRIVAL_DATE     => 'arrival',
        ResourceInterface::DEPARTURE_DATE   => 'departure',
        ResourceInterface::LOCATION_ID      => 'park_id',
    ];

    public function getResult()
    {
        $prices = parent::getResult();

        $locationId = isset( $this->inputParams[ResourceInterface::LOCATION_ID]) ? $this->inputParams[ResourceInterface::LOCATION_ID] : null;

        if (count($prices) == 0) {
            $this->setErrorString('Error: No locations found for location `'. $locationId .'`.');
            return null;
        }
        if (count($prices) > 1) {
            $this->setErrorString('Error: Multiple locations found for location `'. $locationId .'`.');
            return null;
        }

        return $prices[0];
    }
}