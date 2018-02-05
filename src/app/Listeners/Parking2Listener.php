<?php

namespace App\Listeners\Resources2;

use Agent;
use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Models\Website;
use ArrayObject;
use Illuminate\Events\Dispatcher;

class Parking2Listener
{
    static protected $optionsOrder = [
        6, // Sleutel behouden
        4, // Overdekt
        1, // Wassen
        12, // Electrisch laden
    ];

    const PARKING_SPECIFIC = ['loc'];

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.product.parking2.process.input', [$this, 'enrichGeo']);
        $events->listen('resource.product.parking2.process.input', [$this, 'orTaxi'], - 1);

        $events->listen('resource.product.parking2.process.after', [$this, 'filterOutZeroPriceProducts']);
        $events->listen('resource.product.parking2.process.after', [$this, 'enrichProductOptions']);
        $events->listen('resource.product.parking2.process.after', [$this, 'multipleCars']);
        $events->listen('resource.contract.parking2.process.input', [$this, 'validateLicenseplateUnknown']);
        $events->listen('resource.contract.parking2.process.input', [$this, 'licenseplateArray']);

        $events->listen('resource.collection.product.parking2.before', [$this, 'set_area_id']);


        $events->listen('resource.product.parking2.process.after', [$this, 'setTaxiDestinationandLocation']);

    }


    // this is used to set area_id as the input area for all taxi rides
    public function set_area_id(Resource $resource, ArrayObject $input, ArrayObject $collection, ArrayObject $resolved, $action, $id = null)
    {
        if($input->offsetExists(ResourceInterface::AREA_ID)){
            $area_id = implode(',', array_filter(is_array($input->offsetGet(ResourceInterface::AREA_ID)) ? $input->offsetGet(ResourceInterface::AREA_ID) : explode(',', $input->offsetGet(ResourceInterface::AREA_ID)), function ($id) {
                return $id != - 1;
            }));
            $collection->exchangeArray(array_map(function ($parking) use ($area_id) {
                    $parking[ResourceInterface::AREA_ID] = $area_id;
                    return $parking;
                }, $collection->getArrayCopy()));
        }
    }

    public function orTaxi(Resource $resource, ArrayObject $input)
    {
        if($input->offsetExists(ResourceInterface::AREA_ID)){
            $input->offsetSet(ResourceInterface::AREA_ID, $input->offsetGet(ResourceInterface::AREA_ID) . ',-1');
        }

        $or = array_only((array) $input, self::PARKING_SPECIFIC);

        if( ! empty($or)){
            $input->offsetSet('$or', [
                ['service' => 'Taxi'],
                $or,
            ]);

            foreach(array_keys($or) as $key){
                $input->offsetUnset($key);
            }
        }
    }


    public function enrichGeo(Resource $resource, ArrayObject $input)
    {
        if( ! isset($input[ResourceInterface::RADIUS])){
            return;
        }
        $radius = $input[ResourceInterface::RADIUS] / 6731;

        if(isset($input[ResourceInterface::DESTINATION])){
            //We are in "Destination" mode
            //Enrich the input with the coordinates.
            $geolocation = ResourceHelper::callResource1('geocoding.google', 'place', [
                'google_place_id' => $input[ResourceInterface::DESTINATION],
            ])['result'];

            if(isset($geolocation, $geolocation['latitude'], $geolocation['longitude'])){
                $input->offsetSet('loc', [
                    '$geoWithin' => [
                        '$centerSphere' => [
                            [$geolocation['latitude'], $geolocation['longitude']],
                            $radius
                        ]
                    ]
                ]);
            }

            $input['destination_google_place'] = $input[ResourceInterface::DESTINATION];
            unset($input[ResourceInterface::DESTINATION]);
        }elseif(isset($input[ResourceInterface::AREA_ID])){
            //We are in "Area" mode
            //Use for backward compatibility only
            // The old way with fixed locations
            $area = DocumentHelper::show('area', 'parking2', $input[ResourceInterface::AREA_ID]);
            if(isset($area['latitude'], $area['longitude'])){
                $input->offsetSet('loc', [
                    '$geoWithin' => [
                        '$centerSphere' => [
                            [$area['latitude'], $area['longitude']],
                            $radius
                        ]
                    ]
                ]);
            }
        }
    }


    public static function setTaxiDestinationandLocation(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(array_has((array) $input, ResourceInterface::POSTAL_CODE)){
            $output->exchangeArray(array_map(function ($taxi) use ($input) {
                return ($taxi['resource']['name'] === 'rides.taxitender') ? array_merge($taxi, ['destination' => $taxi['area_id']['name'], 'location' => array_get((array) $input, ResourceInterface::POSTAL_CODE)]) : $taxi;

            }, $output->getArrayCopy()));
        }
    }

    public static function enrichProductOptions(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }
        $output->exchangeArray(array_map(function ($product) {

            if($product[ResourceInterface::AVAILABLE_OPTIONS]){
                $product[ResourceInterface::AVAILABLE_OPTIONS] = array_map('intval', explode(',', $product[ResourceInterface::AVAILABLE_OPTIONS]));
            }else{
                $product[ResourceInterface::AVAILABLE_OPTIONS] = [];
            }

            // Retain original location options
            $product[ResourceInterface::PRODUCT_OPTIONS] = $product[ResourceInterface::OPTIONS] ? $product[ResourceInterface::OPTIONS] : [];

            // Make options capitalized
            $product[ResourceInterface::PRODUCT_OPTIONS] = array_map(function ($option) {
                $option[ResourceInterface::NAME] = ucfirst($option[ResourceInterface::NAME]);

                return $option;
            }, $product[ResourceInterface::PRODUCT_OPTIONS]);

            // Sort options & filter options by availability
            $product[ResourceInterface::OPTIONS] = [];
            foreach(self::$optionsOrder as $optionsOrderId){
                if( ! in_array($optionsOrderId, $product[ResourceInterface::AVAILABLE_OPTIONS])){
                    continue;
                }

                // Get option by id
                $option = head(array_filter($product[ResourceInterface::PRODUCT_OPTIONS], function ($option) use ($optionsOrderId) {
                    return $option['id'] == $optionsOrderId;
                }));

                if( ! $option){
                    continue;
                }

                $product[ResourceInterface::OPTIONS][] = $option;
            }

            return $product;
        }, $output->getArrayCopy()));
    }


    /**
     * Multiply stuff for multiple cars
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function multipleCars(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! isset($input[ResourceInterface::NUMBER_OF_CARS]) || $input[ResourceInterface::NUMBER_OF_CARS] < 2){
            return;
        }
        $output->exchangeArray(array_map(function ($product) use ($input) {
            $factor = $input[ResourceInterface::NUMBER_OF_CARS];
            $product[ResourceInterface::PRICE_ACTUAL] *= $factor;
            if( ! isset($product[ResourceInterface::OPTIONS])){
                return $product;
            }
            $product[ResourceInterface::OPTIONS] = array_map(function ($option) use ($factor) {
                $option['cost'] *= $factor;

                return $option;
            }, $product[ResourceInterface::OPTIONS]);

            return $product;
        }, $output->getArrayCopy()));
    }

    /**
     * If a price call fails, the price of the product will be 0. These products should not be shown.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function filterOutZeroPriceProducts(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }

        $products = [];
        foreach($output->getArrayCopy() as $key => $value){
            if($value[ResourceInterface::PRICE_ACTUAL] != 0){
                $products[] = $value;
            }
        }
        $output->exchangeArray($products);
    }

    /**
     * Covert licenseplate fields to array. Fuck me thats ugly
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function licenseplateArray(Resource $resource, ArrayObject $input)
    {
        $inputArr = $input->getArrayCopy();
        if( ! isset($inputArr[ResourceInterface::LICENSEPLATE2])){
            return;
        }
        //merge array
        $licenseplateArr = [$inputArr[ResourceInterface::LICENSEPLATE]];
        for($id = 2; $id <= 6; $id ++){
            if(isset($inputArr[ResourceInterface::LICENSEPLATE . $id])){
                $licenseplateArr[] = $inputArr[ResourceInterface::LICENSEPLATE . $id];
                unset($inputArr[ResourceInterface::LICENSEPLATE . $id]);
            }
        }
        $inputArr[ResourceInterface::LICENSEPLATE] = $licenseplateArr;
        $input->exchangeArray($inputArr);
    }

    public static function validateLicenseplateUnknown(Resource $resource, ArrayObject $input)
    {
        //TODO: Move this validation to Laravel input validation when we switch to Laravel 5.3
        if(empty($input[ResourceInterface::LICENSEPLATE]) && empty($input[ResourceInterface::LICENSEPLATE_UNKNOWN])){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'parking2.error.licenseplate_required',
                    "message" => 'Het kenteken is vereist.',
                    "field"   => ResourceInterface::LICENSEPLATE,
                    "type"    => 'input',
                ]
            ]);
        }else if(empty($input[ResourceInterface::LICENSEPLATE_UNKNOWN]) && ! preg_match('~^[a-zA-Z\d-]+$~', $input[ResourceInterface::LICENSEPLATE])){
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'parking2.error.licenseplate_invalid',
                    "message" => 'Dit is geen geldig kenteken. ',
                    "field"   => ResourceInterface::LICENSEPLATE,
                    "type"    => 'input',
                ]
            ]);
        }
    }

}