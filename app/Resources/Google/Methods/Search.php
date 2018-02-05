<?php

namespace App\Resources\Google\Geocoding\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Google\Geocoding\GeocodingAbstractRequest;

class Search extends GeocodingAbstractRequest
{
    protected $cacheDays = 180;
    protected $clearUnmapped = true;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::FREEFORM_ADDRESS => 'input',
        ResourceInterface::LANGUAGE => 'language',
    ];
    protected $externalToResultMapping = [
        'description' => ResourceInterface::NAME,
        'place_id' => ResourceInterface::GOOGLE_PLACE_ID,
    ];
    protected $resultTransformations = [
    ];

    public function __construct()
    {
        parent::__construct('place/autocomplete/json');
    }

    public function setParams(array $params)
    {
        $params = $this->mapInputToExternal($params, $this->getDefaultParams());
        $params[ResourceInterface::LANGUAGE] = isset($params[ResourceInterface::LANGUAGE]) ? $params[ResourceInterface::LANGUAGE] : $this->getDefaultLanguage();
        $this->params = $params;
    }

    public function executeFunction()
    {
        if (!isset($this->params['input']) || $this->params['input'] === '') {
            // Do not execute function if we have no input
        }
        else
        {
            parent::executeFunction();
        }
    }


    public function getResult()
    {
        if (!isset($this->params['input']) || $this->params['input'] === '') {
            return [];
        }

        $this->resultKeyname = 'predictions';
        return parent::getResult();
    }
}