<?php
namespace App\Resources\Quickparking\Methods;

use App\Resources\AbstractMethodRequest;
use App\Resources\Quickparking\Parking;

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
                Parking::ID => 'qickparking',
                Parking::NAME => 'Quick Parking',
            ]
        ];
    }
}