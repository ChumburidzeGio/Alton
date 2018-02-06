<?php


namespace App\Resources\Taxitender\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;

class RetrieveServiceLocations extends TaxitenderAbstractRequest
{
    protected $cacheDays = 180;

    protected $externalToResultMapping = [
        'shortCode'         => ResourceInterface::AIRPORT_CODE,
        'internationalName' => ResourceInterface::NAME,
    ];

    public function __construct()
    {
        parent::__construct('retrieveServiceLocations');
    }
}