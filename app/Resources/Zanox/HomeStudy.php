<?php
/**
 * User: Roeland Werring
 * Date: 16/05/15
 * Time: 21:45
 * 
 */

namespace App\Resources\Zanox;


class HomeStudy extends AbstractZanoxFeedRequest{

    protected $cacheDays = false;



    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Zanox\Methods\HomeStudy\LoadFeeds::class,
            'description' => 'Loads list of feeds'
        ],
    ];

}