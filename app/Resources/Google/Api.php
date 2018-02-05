<?php

namespace App\Resources\Google\Api;


use App\Resources\AbstractServiceRequest;

class Api extends AbstractServiceRequest
{
    protected $methodMapping = [
        'sheet'          => [
            'class'       => \App\Resources\Google\Api\Methods\Sheet::class,
            'description' => 'Google Sheet'
        ],

        //Yes ?
        'sheetwithoptions'          => [
            'class'       => \App\Resources\Google\Api\Methods\SheetWithOptions::class,
            'description' => 'Get coordinates for a given place id'
        ],
    ];
}