<?php

namespace App\Listeners\Resources2;

use Agent;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Events\Dispatcher;

class Rome2RioListener
{

    public function subscribe(Dispatcher $events)
    {
        $events->listen('resource.routes.travel.rome2rio.process.input', [$this, 'enrichGeo']);
    }


    public function enrichGeo(Resource $resource, ArrayObject $input)
    {
        if(!isset($input[ResourceInterface::DESTINATION_GOOGLE_PLACE_ID]) || !isset($input[ResourceInterface::ORIGIN_GOOGLE_PLACE_ID])){
            return;
        }

        $batchInputs[0] = [
            'google_place_id' => $input[ResourceInterface::ORIGIN_GOOGLE_PLACE_ID],
        ];
        $batchInputs[1] = [
            'google_place_id' => $input[ResourceInterface::DESTINATION_GOOGLE_PLACE_ID],
        ];

        $resourceToCall = Resource::where('name', 'place.geocoding.google')->firstOrFail();
        $geolocation = ParallelServiceListener::batch($resourceToCall, $batchInputs);
        if(isset($geolocation['place.geocoding.google@0'], $geolocation['place.geocoding.google@1'])){
            $newInput = [
                ResourceInterface::ORIGIN_LATITUDE => $geolocation['place.geocoding.google@0'][ResourceInterface::LATITUDE],
                ResourceInterface::ORIGIN_LONGITUDE => $geolocation['place.geocoding.google@0'][ResourceInterface::LONGITUDE],
                ResourceInterface::ORIGIN_ADDRESS_FOR_DISPLAY => $geolocation['place.geocoding.google@0'][ResourceInterface::NAME],
                ResourceInterface::DESTINATION_LATITUDE => $geolocation['place.geocoding.google@1'][ResourceInterface::LATITUDE],
                ResourceInterface::DESTINATION_LONGITUDE => $geolocation['place.geocoding.google@1'][ResourceInterface::LONGITUDE],
                ResourceInterface::DESTINATION_ADDRESS_FOR_DISPLAY => $geolocation['place.geocoding.google@1'][ResourceInterface::NAME],
            ];
            $input->exchangeArray($newInput);
        }
    }
}