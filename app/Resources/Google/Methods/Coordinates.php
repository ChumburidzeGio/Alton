<?php

namespace App\Resources\Google\Geocoding\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Google\Geocoding\GeocodingAbstractRequest;

class Coordinates extends GeocodingAbstractRequest
{
    const DEFAULT_MODE = 'postal_code';
    protected $cacheDays = 180;
    protected $clearUnmapped = true;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::GEO_MODE => 'mode',
        ResourceInterface::STREET=> ResourceInterface::STREET,
        ResourceInterface::HOUSE_NUMBER => ResourceInterface::HOUSE_NUMBER,
        ResourceInterface::HOUSE_NUMBER_SUFFIX => ResourceInterface::HOUSE_NUMBER_SUFFIX,
        ResourceInterface::POSTAL_CODE => ResourceInterface::POSTAL_CODE,
        ResourceInterface::CITY => ResourceInterface::CITY,
        ResourceInterface::COUNTRY_CODE => 'country',
        ResourceInterface::FREEFORM_ADDRESS => ResourceInterface::FREEFORM_ADDRESS,
    ];
    protected $externalToResultMapping = [
        'geometry.location.lat' => ResourceInterface::LATITUDE,
        'geometry.location.lng' => ResourceInterface::LONGITUDE,
        'place_id' => ResourceInterface::GOOGLE_PLACE_ID,
        'formatted_address' => ResourceInterface::FREEFORM_ADDRESS,
    ];
    protected $resultTransformations = [
    ];

    //The adress chunks specify the order of the components that build the address string
    // The value indicates if it is a component filter as well
    protected $addressChunks = [
        ResourceInterface::STREET => 0,
        ResourceInterface::HOUSE_NUMBER => 1,
        ResourceInterface::HOUSE_NUMBER_SUFFIX => 0,
        ResourceInterface::POSTAL_CODE => 1,
        ResourceInterface::CITY => 0,
        'country' => 1
    ];


    public function __construct()
    {
        parent::__construct('geocode/json');
    }

    public function setParams(array $params)
    {
        //Example Dutch Format for accurate results --> Kalverstraat 99, Amsterdam
        $params = $this->mapInputToExternal($params, $this->getDefaultParams());

        //The function can accept a freeform address in freeform mode or one or more address params in relaxed mode
        //The default mode looks using the post code
        if(isset($params['mode'])){
            switch ($params['mode']){
                case 'relaxed':
                    $this->params = $this->createRelaxedParams($params);
                    break;
                case 'freeform':
                    $this->params['address'] = $params[ResourceInterface::FREEFORM_ADDRESS];
                    break;
            }
        }else{
            $this->params['components'] = 'postal_code:' . $params[ResourceInterface::POSTAL_CODE];
        }

        $this->params[ResourceInterface::LANGUAGE] = isset($params[ResourceInterface::LANGUAGE]) ? $params[ResourceInterface::LANGUAGE] : $this->getDefaultLanguage();
    }

    /**
     * @param array $input
     * @return array
     */
    private function createRelaxedParams(array $input)
    {
        $params['address'] = '';
        //Use the address chunks to build the address field
        //and the component filters field e.g postal_code:1012GL|country:NL

        foreach ($this->addressChunks as $addressChunk => $isComponentFilter){
            if(isset($input[$addressChunk])){
                $params['address'] = $params['address'] . $input[$addressChunk] . ' ';
                if($isComponentFilter){
                    $params['components'][] = $addressChunk.':' . $input[$addressChunk];
                }
            }
        }
        if(isset($params['components'])){
            $params['components'] = implode('|', $params['components']);
        }

        return $params;
    }

}