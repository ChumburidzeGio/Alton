<?php

namespace App\Resources\Parkingci\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Parkingci\ParkingciAbstractRequest;

class Options extends ParkingciAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [
        'id'   => ResourceInterface::ID,
        'name' => ResourceInterface::NAME,
    ];
    protected $resultTransformations = [];

    protected $resultKeyname = 'parkeeroptions';

    public function __construct()
    {
        parent::__construct('options');
    }
}