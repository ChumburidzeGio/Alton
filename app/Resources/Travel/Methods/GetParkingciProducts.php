<?php

namespace App\Resources\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Travel\TravelWrapperAbstractRequest;

class GetParkingciProducts extends TravelWrapperAbstractRequest
{
    public function executeFunction()
    {
        $this->result = $this->getParkingCiProducts();
    }

    protected function getParkingCiProducts()
    {
        $names = [];

        return array_map(function ($location) use (&$names) {
            $product = [];

            // Make sure the ID is consistent over multiple updates (mongo ObjectID)
            $product['_id'] = substr(md5('travel.parkingci.location_id.'. $location[ResourceInterface::LOCATION_ID]), 0, 24);
            $product['__id'] = $product['_id'];

            $product += array_only($location, [
                ResourceInterface::ENABLED,
                ResourceInterface::DESCRIPTION,
                ResourceInterface::DESCRIPTION_DE,
                ResourceInterface::DESCRIPTION_FR,
                ResourceInterface::DESCRIPTION_EN,
                ResourceInterface::BRAND_LOGO,
                ResourceInterface::OFFICIAL,
                ResourceInterface::SOURCE,
                ResourceInterface::SERVICE,
                ResourceInterface::OPTIONS,
                ResourceInterface::NIGHT_SURCHARGE,
                ResourceInterface::CONDITIONS,
                ResourceInterface::MAP_IMAGE,
                ResourceInterface::ECO_POINTS,
                ResourceInterface::EMAIL1,
                ResourceInterface::EMAIL2,
                ResourceInterface::EMAIL_FROM,
                ResourceInterface::TYPE,
            ]);
            $product[ResourceInterface::ACTIVE] = true;

            $product[ResourceInterface::TITLE] = $location['name'];

            if (array_key_exists($product[ResourceInterface::TITLE], $names)) {
                $product[ResourceInterface::TITLE] .= ' ' . ++$names[$product[ResourceInterface::TITLE]];
            } else {
                $names[$product[ResourceInterface::TITLE]] = 1;
            }

            $product[ResourceInterface::LOCATION_ID] = (int) $location[ResourceInterface::AIRPORT_ID];
            $product[ResourceInterface::PARKING_ID] = $location[ResourceInterface::LOCATION_ID];
            $product[ResourceInterface::PRODUCT_OPTIONS_IDS] = array_map(function ($option) {
                return intval($option['id']);
            }, (array) $location[ResourceInterface::OPTIONS]);
            $product[ResourceInterface::IS_OFFICIAL_FACILITY] = $location[ResourceInterface::OFFICIAL];
            $product[ResourceInterface::DISTANCE_TO_DESTINATION] = (float)$location[ResourceInterface::DISTANCE];
            $product[ResourceInterface::TIME_TO_DESTINATION] = (float)$location[ResourceInterface::TIME];


            if ($location['company.name'] == 'taxiboeken') {
                $product[ResourceInterface::RESOURCE][ResourceInterface::NAME] = 'prices.taxiboeken';
                $product[ResourceInterface::RESOURCE][ResourceInterface::ID] = $location[ResourceInterface::CATEGORY];
            }
            else if ($location['company.name'] == 'taxitender') {
                $product[ResourceInterface::RESOURCE][ResourceInterface::NAME] = 'prices.taxitender';
                $product[ResourceInterface::RESOURCE][ResourceInterface::ID] = $location[ResourceInterface::CATEGORY];
            }
            else if (in_array($location[ResourceInterface::SOURCE], ['parkingpro', 'schipholparking', 'parkandfly'])) { // Todo: Add other parking APIs we map directly
                $product[ResourceInterface::RESOURCE][ResourceInterface::NAME] = 'prices.'.$location[ResourceInterface::SOURCE];
                $product[ResourceInterface::RESOURCE][ResourceInterface::ID] = $location[ResourceInterface::RESOURCE__ID];
            }
            else
            {
                $product[ResourceInterface::RESOURCE][ResourceInterface::NAME] = 'prices.parkingci';
                $product[ResourceInterface::RESOURCE][ResourceInterface::ID] = $location[ResourceInterface::LOCATION_ID];
            }

            // Put "Overnachten + Parkeren (statische prijzen)" products into "Parkeren + Overnachten"
            if ($product[ResourceInterface::SERVICE] == 9)
                $product[ResourceInterface::SERVICE] = 4;

            $product[ResourceInterface::IMAGES] = array_values(array_filter(array_map(function ($i) use (&$location) {
                $img = $location['image' . $i];
                unset($location['image' . $i]);

                return $img;
            }, range(1, 10))));

            $product[ResourceInterface::COMPANY__ID] = $location[ResourceInterface::COMP_ID];

            //TODO: move these checks to somewhere else to automatically change empty strings into null
            $product[ResourceInterface::MAIL] = (isset($location[ResourceInterface::MAIL][0]) && !empty($location[ResourceInterface::MAIL][0])) ? $location[ResourceInterface::MAIL][0] : null;
            $product[ResourceInterface::MAIL_EN] = (isset($location[ResourceInterface::MAIL_EN][0]) && !empty($location[ResourceInterface::MAIL_EN][0])) ? $location[ResourceInterface::MAIL_EN][0] : null;
            $product[ResourceInterface::MAIL_DE] = (isset($location[ResourceInterface::MAIL_DE][0]) && !empty($location[ResourceInterface::MAIL_DE][0])) ? $location[ResourceInterface::MAIL_DE][0] : null;
            $product[ResourceInterface::MAIL_FR] = (isset($location[ResourceInterface::MAIL_FR][0]) && !empty($location[ResourceInterface::MAIL_FR][0])) ? $location[ResourceInterface::MAIL_FR][0] : null;
            $product[ResourceInterface::EMAIL1] = (isset($location[ResourceInterface::EMAIL1]) && !empty($location[ResourceInterface::EMAIL1])) ? $location[ResourceInterface::EMAIL1] : null;
            $product[ResourceInterface::EMAIL2] = (isset($location[ResourceInterface::EMAIL2]) && !empty($location[ResourceInterface::EMAIL2])) ? $location[ResourceInterface::EMAIL2] : null;
            $product[ResourceInterface::ECO_POINTS] = (isset($location[ResourceInterface::ECO_POINTS]) && !empty($location[ResourceInterface::ECO_POINTS])) ? $location[ResourceInterface::ECO_POINTS] : null;

            $product[ResourceInterface::LOCATION_DESCRIPTION] = $location[ResourceInterface::LOCATION];

            $product[ResourceInterface::TYPE] = array_get($product, ResourceInterface::TYPE, 'bookable');


            return $product;
        }, $this->internalRequest('parkingci', 'locations'));
    }
}