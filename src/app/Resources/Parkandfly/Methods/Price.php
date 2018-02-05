<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;

class Price extends Prices
{
	public function setParams(Array $params)
    {
        if (!isset($params[ResourceInterface::LOCATION_ID]))
        {
            $this->setErrorString('Parameter `location_id` is required.');
            return [];
        }

        return parent::setParams($params);
    }

    public function getResult()
    {
        $prices = [];
        foreach (parent::getResult() as $price)
        {
            // If only want one specific location, skip all others
            if ($price[ResourceInterface::LOCATION_ID] != $this->inputParams[ResourceInterface::LOCATION_ID])
                continue;

            $prices[] = $price;
        }

        if (count($prices) == 0) {
            // Park and Fly does not distinguish between non-existent and non-available locations.
            // So 'not found' might mean 'not available'
            return [
                ResourceInterface::LOCATION_ID => $this->inputParams[ResourceInterface::LOCATION_ID],
                ResourceInterface::PRICE_ACTUAL => 0,
                ResourceInterface::IS_UNAVAILABLE => true,
            ];
        }
        if (count($prices) > 1) {
            $this->setErrorString('Error: Multiple locations found for location `'. $this->inputParams[ResourceInterface::LOCATION_ID] .'`.');
            return null;
        }

        return $prices[0];
    }
}