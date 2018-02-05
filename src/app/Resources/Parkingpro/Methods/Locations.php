<?php
namespace App\Resources\Parkingpro\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;

class Locations extends ParkingproAbstractRequest
{
    protected $cacheDays = 1;

    protected $inputToExternalMapping = [
        ResourceInterface::PARKING_ID => 'parkingId',
    ];
    protected $externalToResultMapping = [
        'id'                => ResourceInterface::LOCATION_ID,
        'name'              => ResourceInterface::NAME,
        'description'       => ResourceInterface::DESCRIPTION,
    ];

    public function __construct()
    {
        parent::__construct('locations');
    }
}