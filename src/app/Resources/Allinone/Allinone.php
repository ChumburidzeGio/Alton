<?php

namespace App\Resources\Allinone;

use App\Resources\AbstractServiceRequest;

class Allinone extends AbstractServiceRequest
{
    protected $methodMapping = [
        'request' => [
            'class'       => \App\Resources\Allinone\Methods\Request::class,
            'description' => '',
        ],
        'contract' => [
            'class'       => \App\Resources\Allinone\Methods\Contract::class,
            'description' => '',
        ],
    ];
}