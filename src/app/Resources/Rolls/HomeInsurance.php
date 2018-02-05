<?php
/**
 * User: Roeland Werring
 * Date: 23/02/15
 * Time: 11:57
 * 
 */

namespace App\Resources\Rolls;

use App;

class HomeInsurance extends RollsServiceRequest
{

    protected $methodMapping = [
        'list'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\KeuzeLijstenClient::class,
            'description' => 'Request list of choosable options'
        ],
        'products'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\OpstalProductenLijstClient::class,
            'description' => 'Request list of products'
        ],
        'premium'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\OpstalPremieBerekenClient::class,
            'description' => '(NOT READY) Request list of products'
        ],
    ];


}