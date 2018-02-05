<?php

namespace App\Resources\Rome2Rio\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Rome2Rio\Travel\TravelAbstractRequest;

class Coordinates extends TravelAbstractRequest
{
    protected $cacheDays = false;
    protected $clearUnmapped = true;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [
        ResourceInterface::SEARCH_QUERY => 'query'
    ];
    protected $externalToResultMapping = [
    ];
    protected $resultTransformations = [
    ];

    public function __construct()
    {
        parent::__construct('Geocode');
    }

    public function setParams(array $params)
    {
        $params = $this->mapInputToExternal($params, $this->getDefaultParams());
        $this->params['query'] = $params['query'];

    }

}