<?php

namespace App\Resources\Parkingci\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkingci\ParkingciAbstractRequest;

class Services extends ParkingciAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'id'   => ResourceInterface::ID,
        'name' => ResourceInterface::NAME,
    ];
    protected $resultTransformations = [];

    protected $resultKeyname = 'service';

    public function __construct()
    {
        parent::__construct('service');
    }
}