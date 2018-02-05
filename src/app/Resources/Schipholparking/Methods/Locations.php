<?php
namespace App\Resources\Schipholparking\Methods;
use App\Resources\Schipholparking\Parking;

/**
 * Class Locations
 *
 * Extends 'Prices', because it uses the same request, and just strips out price-data.
 *
 * @package App\Resources\Schipholparking\Methods
 */
class Locations extends Prices
{
    protected $cacheDays = false;

    public function setParams(Array $params)
    {
        // Pick placeholder near-future period range
        $params['StartDate'] = date('c', strtotime('+1 days 12:00'));
        $params['EndDate'] = date('c', strtotime('+2 days 12:00'));

        return parent::setParams($params);
    }

    public function getResult()
    {
        $locations = [];
        foreach (parent::getResult() as $price)
        {
            // Remove price/availability data
            unset(
                $price[Parking::PRICE_ACTUAL],
                $price[Parking::IS_UNAVAILABLE],
                $price['@unmapped']['spacesremaining']
            );

            $locations[] = $price;
        }

        return $locations;
    }
}