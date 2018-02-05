<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 21:45
 * 
 */

namespace App\Resources\Zanox;


class SimOnly extends AbstractZanoxFeedRequest{

    protected $cacheDays = false;



    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Zanox\Methods\SimOnly\LoadFeeds::class,
            'description' => 'Loads list of feeds'
        ],
    ];

}