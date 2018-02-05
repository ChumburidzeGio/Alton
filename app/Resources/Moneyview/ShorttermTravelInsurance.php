<?php
/**
 * User: Roeland Werring
 * Date: 09/03/15
 * Time: 13:31
 *
 */
namespace App\Resources\Moneyview;

use App;

class ShorttermTravelInsurance extends MoneyviewServiceRequest
{
    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\ShorttermTravel\ProductListClient::class,
            'description' => 'Request list of products'
        ],
        'list'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\ShorttermTravel\ChoiceListClient::class,
            'description' => 'Request list of products'
        ],
        'premium'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\ShorttermTravel\PremiumClient::class,
            'description' => 'Requests premium by various arguments'
        ],
        'policy'     => [
            'class'       => \App\Resources\Moneyview\Methods\Impl\ShorttermTravel\PolicyClient::class,
            'description' => 'Requests policy by company and coverage'
        ],
    ];
}