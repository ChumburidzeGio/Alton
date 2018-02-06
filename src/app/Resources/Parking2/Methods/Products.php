<?php

namespace App\Resources\Parking2\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\Parking2WrapperAbstractRequest;

class Products extends Parking2WrapperAbstractRequest
{
    public function executeFunction()
    {
        $this->result = $this->getParkingCiProducts();
    }

    protected function getParkingCiProducts()
    {
        return array_map(function ($location) {
            // Make sure the ID is consistent over multiple updates (mongo ObjectID)
            $location['_id'] = substr(md5('parkingci.location_id.'. $location[ResourceInterface::LOCATION_ID]), 0, 24);

            $location[ResourceInterface::AREA_ID] = (int) $location[ResourceInterface::AIRPORT_ID];
            $location[ResourceInterface::RESOURCE] = [
                ResourceInterface::ID   => $location[ResourceInterface::LOCATION_ID],
                ResourceInterface::NAME =>
                    $location['company.name'] == 'taxitender'
                        ? 'rides.taxitender' : 'prices.parkingci',
            ];
            $location[ResourceInterface::TITLE]    = $location[ResourceInterface::NAME];
            $location[ResourceInterface::ACTIVE]    = $location[ResourceInterface::ENABLED];
            $location[ResourceInterface::PARKING_ID] = $location[ResourceInterface::LOCATION_ID];
            $location[ResourceInterface::AVAILABLE_OPTIONS] = array_map(function ($option) {
                return intval($option['id']);
            }, (array) $location[ResourceInterface::OPTIONS]);
            $location[ResourceInterface::IMAGES] = array_values(array_filter(array_map(function ($i) use (&$location) {
                $img = $location['image' . $i];
                unset($location['image' . $i]);

                return $img;
            }, range(1, 10))));

            // Change company.id etc.  into array: company[id, name, image, title]
            foreach([ResourceInterface::COMP_ID, ResourceInterface::COMP_NAME, ResourceInterface::COMP_IMAGE, ResourceInterface::COMP_TITLE] as $c)
            {
                $location['company'][substr($c, strpos($c, ".") + 1)] = $location[$c];
                unset($location[$c]);
            }

            //TODO: move these checks to somewhere else to automatically change empty strings into null
            $location[ResourceInterface::MAIL] = (isset($location[ResourceInterface::MAIL][0]) && !empty($location[ResourceInterface::MAIL][0])) ? $location[ResourceInterface::MAIL][0] : null;
            $location[ResourceInterface::MAIL_EN] = (isset($location[ResourceInterface::MAIL_EN][0]) && !empty($location[ResourceInterface::MAIL_EN][0])) ? $location[ResourceInterface::MAIL_EN][0] : null;
            $location[ResourceInterface::MAIL_DE] = (isset($location[ResourceInterface::MAIL_DE][0]) && !empty($location[ResourceInterface::MAIL_DE][0])) ? $location[ResourceInterface::MAIL_DE][0] : null;
            $location[ResourceInterface::EMAIL1] = (isset($location[ResourceInterface::EMAIL1]) && !empty($location[ResourceInterface::EMAIL1])) ? $location[ResourceInterface::EMAIL1] : null;
            $location[ResourceInterface::EMAIL2] = (isset($location[ResourceInterface::EMAIL2]) && !empty($location[ResourceInterface::EMAIL2])) ? $location[ResourceInterface::EMAIL2] : null;

            unset($location[ResourceInterface::AIRPORT_ID]);
            unset($location[ResourceInterface::LOCATION_ID]);
            unset($location[ResourceInterface::UNMAPPED]);

            return $location;
        }, $this->internalRequest('parkingci', 'locations'));
    }
}