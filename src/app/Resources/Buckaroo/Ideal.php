<?php

namespace App\Resources\Buckaroo;

use App\Resources\AbstractServiceRequest;

class Ideal extends AbstractServiceRequest
{
    protected $methodMapping = [
        'payment' => [
            'class'       => \App\Resources\Buckaroo\Methods\Payment::class,
            'description' => 'Make payment'
        ]
    ];

}


?>