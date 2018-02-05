<?php

namespace App\Listeners\Resources2;

use Agent;
use App\Exception\PrettyServiceError;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\App;

class GlobalGeoListener
{

    const MEAN_RADIUS_EARTH_KM = 6731;

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.product.travel.process.input', [$this, 'enrichInputAddressesWithGeo']);
        $events->listen('resource.prices.taxitender.process.input', [$this, 'enrichInputAddressesWithGeo']);
        $events->listen('resource.prices.taxiboeken.process.input', [$this, 'enrichInputAddressesWithGeo']);
        $events->listen('resource.order.travel.process.input', [$this, 'enrichInputAddressesWithGeo']);
        $events->listen('resource.search.geocoding.google.process.input', [$this, 'applyLocale']);

        $events->listen('resource.find_bookable_rides.taxitender.process.input', [$this, 'enrichInputAddressesWithGeo']);
    }

    public function applyLocale(Resource $resource, ArrayObject $input, $action){
        $language = substr(App::getLocale(), 0, 2);
        if($language !== false){
            $input->offsetSet(ResourceInterface::LANGUAGE, $language);
        }
    }

    public function enrichInputAddressesWithGeo(Resource $resource, ArrayObject $input)
    {
        foreach (['destination', 'origin'] as $where) {
            if (isset($input[$where . '_latitude']) && isset($input[$where . '_longitude'])) {
                continue;
            }

            if (isset($input[$where . '_' . ResourceInterface::GOOGLE_PLACE_ID]) && strpos($input[$where . '_' . ResourceInterface::GOOGLE_PLACE_ID], 'search:') === 0){
                $searchString = str_replace('search:', '', $input[$where . '_' . ResourceInterface::GOOGLE_PLACE_ID]);
                try {
                    $googlePlaces = ResourceHelper::callResource2('search.geocoding.google', [
                        ResourceInterface::FREEFORM_ADDRESS => $searchString,
                        ResourceInterface::LANGUAGE => 'en',
                    ]);
                    if (isset($googlePlaces[0], $googlePlaces[0]['google_place_id'])) {
                        $input[$where . '_' . ResourceInterface::GOOGLE_PLACE_ID] = $googlePlaces[0]['google_place_id'];
                    } else {
                        throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Cannot find a google place matching `'. $searchString .'`.');
                    }
                }
                catch (\Exception $e)
                {
                    throw new PrettyServiceError($resource, $input->getArrayCopy(), 'Error occures while trying to find google place matching `'. $searchString .'`.');
                }
            }

            if(isset($input[$where . '_' . ResourceInterface::GOOGLE_PLACE_ID])){
                $geolocation = ResourceHelper::callResource1('geocoding.google', 'place', [
                    ResourceInterface::GOOGLE_PLACE_ID => $input[$where . '_' . ResourceInterface::GOOGLE_PLACE_ID],
                    ResourceInterface::LANGUAGE => 'en',
                ]);

                if (isset($geolocation, $geolocation['result'][ResourceInterface::LATITUDE], $geolocation['result'][ResourceInterface::LONGITUDE])) {
                    foreach(['latitude', 'longitude', 'country_code', 'country_name', 'city', 'street', 'postal_code', 'house_number', 'point_of_interest'] as $field) {
                        if (isset($geolocation['result'][$field])) {
                            $input[$where . '_' . $field] = $geolocation['result'][$field];
                        }
                    }
                }
                $input[$where . '_address'] = array_get($input, $where . '_street') .' '. array_get($input, $where . '_house_number') .', '.  array_get($input, $where . '_city') .', '.  array_get($input, $where . '_country_name');
            }
            elseif (isset($input[$where . '_address']) && empty($input[$where . '_longitude'])) {
                $googlePlace = ResourceHelper::callResource1('geocoding.google', 'search', [
                    ResourceInterface::FREEFORM_ADDRESS => $input[$where . '_address'],
                ]);

                if (!isset($googlePlace['result'][0]))
                    continue;

                $geolocation = ResourceHelper::callResource1('geocoding.google', 'place', [
                    ResourceInterface::GOOGLE_PLACE_ID => $googlePlace['result'][0][ResourceInterface::GOOGLE_PLACE_ID],
                ]);

                if (isset($geolocation, $geolocation['result'][ResourceInterface::LATITUDE], $geolocation['result'][ResourceInterface::LONGITUDE])) {
                    $input[$where . '_google_place_id'] = ResourceInterface::GOOGLE_PLACE_ID;
                    $input[$where . '_google_place_id_value'] = $geolocation['result'][ResourceInterface::NAME];
                    $input[$where . '_latitude'] = $geolocation['result'][ResourceInterface::LATITUDE];
                    $input[$where . '_longitude'] = $geolocation['result'][ResourceInterface::LONGITUDE];
                }
            }
        }
    }
}