<?php
/**
 * User: Roeland Werring
 * Date: 03/09/15
 * Time: 20:07
 * 
 */
namespace App\Resources\Csv;

use App\Resources\AbstractServiceRequest;

class Foodbox extends AbstractServiceRequest{

    protected $cacheDays = false;

    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Csv\Foodbox\LoadCsv::class,
            'description' => 'Loads list of feeds'
        ],
    ];

}

