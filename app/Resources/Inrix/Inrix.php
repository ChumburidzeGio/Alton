<?php

namespace App\Resources\Inrix;

use App\Resources\AbstractServiceRequest;

class Inrix extends AbstractServiceRequest
{
    protected $methodMapping = [
        'parking_lots'              => [
            'class'       => \App\Resources\Inrix\Methods\ParkingLots::class,
            'description' => 'Search for parking lots.'
        ],
        'auth_app_token'              => [
            'class'       => \App\Resources\Inrix\Methods\AuthAppToken::class,
            'description' => 'Get an authentication token.'
        ],
    ];

}
