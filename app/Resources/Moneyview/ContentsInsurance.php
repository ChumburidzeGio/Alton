<?php
/**
 * User: Roeland Werring
 * Date: 09/03/15
 * Time: 13:31
 *
 */
namespace App\Resources\Moneyview;

use App;

class ContentsInsurance extends MoneyviewServiceRequest
{
    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Contents\ProductListClient::class,
            'description' => 'Request list of products'
        ],
        'list'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Contents\ChoiceListClient::class,
            'description' => 'Request list of products'
        ],
        'premium'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Contents\PremiumClient::class,
            'description' => 'Requests premium by various arguments'
        ],
        'policy'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\Contents\PolicyClient::class,
            'description' => 'Requests policy by company and coverage'
        ],
    ];

    protected $filterMapping = [
    ];

}