<?php

namespace App\Resources\Google\Geocoding\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Google\Geocoding\GeocodingAbstractRequest;

class Place extends GeocodingAbstractRequest
{
    protected $cacheDays = 180;
    protected $clearUnmapped = true;
    protected $resultKeyname = 'result';

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::GOOGLE_PLACE_ID => 'place_id',
        ResourceInterface::LANGUAGE => 'language'
    ];
    protected $externalToResultMapping = [
        'geometry.location.lat' => ResourceInterface::LATITUDE,
        'geometry.location.lng' => ResourceInterface::LONGITUDE,
        'place_id' => ResourceInterface::GOOGLE_PLACE_ID,
        'formatted_address' => ResourceInterface::NAME,
        'url'=> ResourceInterface::GOOGLE_MAPS_URL,
        'country_code' => ResourceInterface::COUNTRY_CODE,
        'country_name' => ResourceInterface::COUNTRY_NAME,
        'postal_code' => ResourceInterface::POSTAL_CODE,
        'city' => ResourceInterface::CITY,
        'street' => ResourceInterface::STREET,
        'house_number' => ResourceInterface::HOUSE_NUMBER,
        'point_of_interest' => ResourceInterface::POINT_OF_INTEREST,
    ];
    protected $resultTransformations = [
    ];

    public function __construct()
    {
        parent::__construct('place/details/json');
    }

    public function setParams(array $params)
    {
        $params = $this->mapInputToExternal($params, $this->getDefaultParams());
        $params[ResourceInterface::LANGUAGE] = isset($params[ResourceInterface::LANGUAGE]) ? $params[ResourceInterface::LANGUAGE] :  $this->getDefaultLanguage();
        $this->params = $params;
    }

    public function getResult()
    {
        foreach(array_get($this->result['result'], 'address_components', []) as $component) {
            if (in_array('country', $component['types'])) {
                $this->result['result']['country_code'] = array_get($component, 'short_name');
                $this->result['result']['country_name'] = array_get($component, 'long_name');
            }
            if (in_array('street_number', $component['types'])) {
                $this->result['result']['house_number'] = array_get($component, 'long_name');
            }
            if (in_array('route', $component['types'])) {
                $this->result['result']['street'] = array_get($component, 'long_name');
            }
            if (in_array('postal_code', $component['types'])) {
                $this->result['result']['postal_code'] = array_get($component, 'long_name');
            }
            if (in_array('locality', $component['types'])) {
                $this->result['result']['city'] = array_get($component, 'long_name');
            }
        }

        if (stristr($this->result['result']['name'], 'airport') or in_array('point_of_interest', $this->result['result']['types'])) {
            $this->result['result']['point_of_interest'] = $this->result['result']['name'];
        }

        return parent::getResult();
    }

}