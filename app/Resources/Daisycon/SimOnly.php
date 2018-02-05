<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 21:45
 * 
 */

namespace App\Resources\Daisycon;


use App\Resources\AbstractServiceRequest;

class SimOnly extends AbstractServiceRequest{

    protected $cacheDays = false;



    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Daisycon\Methods\SimOnly\LoadFeeds::class,
            'description' => 'Loads list of feeds'
        ],
    ];

}