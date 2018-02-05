<?php
namespace App\Resources\Schipholparking\Methods;

use App\Resources\AbstractMethodRequest;
use App\Resources\Schipholparking\Parking;

class Parkings extends AbstractMethodRequest
{
    public function executeFunction()
    {
        // No actual request needs to be made
    }

    public function getResult()
    {
        // Schiphol has only one 'parking' -> 'Schiphol Parking'
        return [
            [
                Parking::PARKING_ID => 'schipholparking',
                Parking::NAME => 'Schiphol Parking',
            ]
        ];
    }
}