<?php

namespace App\Resources\Parkingci\Methods;


use App\Resources\Parkingci\ParkingciAbstractRequest;

class UpdateReservation extends CreateReservation
{
    public function __construct()
    {
        // Skips CreateReservation::__construct()
        $methodPath = $this->isTestEnvironment() ? 'order_test/{order_id}' : 'order/{order_id}';
        ParkingciAbstractRequest::__construct($methodPath, self::METHOD_PUT);
    }
}