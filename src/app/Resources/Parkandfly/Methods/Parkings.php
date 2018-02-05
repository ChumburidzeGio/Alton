<?php
namespace App\Resources\Parkandfly\Methods;

use App\Resources\AbstractMethodRequest;
use App\Resources\Parkandfly\Parking;

class Parkings extends AbstractMethodRequest
{
    public function executeFunction()
    {
        // No actual request needs to be made
    }

    public function getResult()
    {
        return [
            [
                Parking::PARKING_ID => 'parkandfly',
                Parking::NAME => 'Park & Fly',
            ]
        ];
    }
}