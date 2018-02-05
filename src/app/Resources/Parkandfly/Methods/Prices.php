<?php
namespace App\Resources\Parkandfly\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkandfly\ParkandflyAbstractRequest;
use GuzzleHttp\Message\ResponseInterface;

class Prices extends ParkandflyAbstractRequest
{
    // To get all locations, think big
    const GETALL_MAX_DISTANCE = 1000000;
    const GETALL_MAX_RESULTS = 1000;

    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::ARRIVAL_DATE     => 'formatDateTime',
        ResourceInterface::DEPARTURE_DATE   => 'formatDateTime',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::ARRIVAL_DATE     => 'arrival',
        ResourceInterface::DEPARTURE_DATE   => 'departure',
    ];
    protected $externalToResultMapping = [
        'id'            => ResourceInterface::LOCATION_ID,
        'price'         => ResourceInterface::PRICE_ACTUAL,
        'available'     => ResourceInterface::IS_UNAVAILABLE,
    ];
    protected $resultTransformations = [
        ResourceInterface::IS_UNAVAILABLE => 'formatIsUnavailable',
        ResourceInterface::LOCATION_ID    => 'castToString',
    ];

    public function __construct()
    {
        parent::__construct('locations/find', self::METHOD_POST);
    }

    protected function getDefaultParams()
    {
        return [
            'lat' => (string)self::DEFAULT_SEARCH_LATLON['lat'],
            'lng' => (string)self::DEFAULT_SEARCH_LATLON['lng'],
            'arrival' => null,
            'departure' => null,
            'maxDistance' => self::GETALL_MAX_DISTANCE,
            'maxResults' =>  self::GETALL_MAX_RESULTS,
            'nrOfCars' => null,
        ];
    }

    protected function formatIsUnavailable($value)
    {
        return !$value;
    }

    public function parseResponse(ResponseInterface $response, $ignoreException = false)
    {
        $result = parent::parseResponse($response, $ignoreException);
        if (isset($result['locations']))
            $result = $result['locations'];

        return $result;
    }
}