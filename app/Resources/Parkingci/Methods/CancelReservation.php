<?php

namespace App\Resources\Parkingci\Methods;


use App\Resources\Parkingci\ParkingciAbstractRequest;

class CancelReservation extends ParkingciAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [];
    protected $inputToExternalMapping = [];
    protected $externalToResultMapping = [];
    protected $resultTransformations = [];

    public function __construct()
    {
        $methodPath = $this->isTestEnvironment() ? 'order_test/{order_id}' : 'order/{order_id}';
        parent::__construct($methodPath, self::METHOD_DELETE);
    }
}