<?php
/**
 * User: Roeland Werring
 * Date: 23/02/15
 * Time: 11:57
 * 
 */

namespace App\Resources\Rolls;

use App;

class ContentsInsurance extends RollsServiceRequest
{

    protected $methodMapping = [
        'list'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\KeuzeLijstenClient::class,
            'description' => 'Request list of choosable options'
        ],
        'products'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\InboedelProductenLijstClient::class,
            'description' => 'Request list of products'
        ],
        'premium'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\InboedelPremieBerekenClient::class,
            'description' => 'Request premium'
        ],
        'policy'     => [
            'class'       => \App\Resources\Rolls\Methods\Impl\InboedelPolisVoorwaardenClient::class,
            'description' => 'Request policy conditions'
        ],
    ];


}