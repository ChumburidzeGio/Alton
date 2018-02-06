<?php

namespace App\Resources\Nearshoring;

use App\Resources\AbstractServiceRequest;

class Iak extends AbstractServiceRequest
{

    protected $methodMapping = [
        'insurance'     => [
            'class'       => \App\Resources\Nearshoring\Methods\Insurance::class,
            'description' => 'Get contract'
        ],
    ];

}


?>