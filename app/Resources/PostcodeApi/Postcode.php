<?php

namespace App\Resources\PostcodeApi;

use App\Resources\AbstractServiceRequest;

class Postcode extends AbstractServiceRequest
{
    protected $methodMapping = [
        'fetch'          => [
            'class'       => \App\Resources\PostcodeApi\Methods\Fetch::class,
            'description' => 'Get locations'
        ]
    ];
}
