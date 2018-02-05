<?php
namespace App\Resources\Csv;

use App\Resources\AbstractServiceRequest;

class Doginsurance extends AbstractServiceRequest{

    protected $cacheDays = false;

    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Csv\Doginsurance\LoadCsv::class,
            'description' => 'Loads list of feeds'
        ],
    ];

}

