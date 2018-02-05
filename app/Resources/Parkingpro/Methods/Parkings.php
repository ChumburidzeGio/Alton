<?php
namespace App\Resources\Parkingpro\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;


class Parkings extends ParkingproAbstractRequest
{
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'id'                => ResourceInterface::PARKING_ID,
        'name'              => ResourceInterface::NAME,
        'isUnavailable'     => ResourceInterface::IS_UNAVAILABLE,
    ];

    protected $cacheDays = 1;

    public function __construct()
    {
        parent::__construct('parkings');
    }
}