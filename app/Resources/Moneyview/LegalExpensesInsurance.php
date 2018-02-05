<?php
/**
 * User: Roeland Werring
 * Date: 09/03/15
 * Time: 13:31

 */
namespace App\Resources\Moneyview;

use App;

/**
 * Class LegalExpensesInsurance
 * @package App\Resources\Moneyview
 * @method LegalExpensesInsurance premium (array $params, string $path)
 */
class LegalExpensesInsurance extends MoneyviewServiceRequest
{
    protected $methodMapping = [
        'products' => [
            'class'       => Methods\Impl\LegalExpenses\ProductListClient::class,
            'description' => 'Request list of products'
        ],
        'list'     => [
            'class'       => Methods\Impl\LegalExpenses\ChoiceListClient::class,
            'description' => 'Request list of products'
        ],
        'premium'  => [
            'class'       => Methods\Impl\LegalExpenses\PremiumClient::class,
            'description' => 'Requests premium by various arguments'
        ],
        'policy'   => [
            'class'       => Methods\Impl\LegalExpenses\PolicyClient::class,
            'description' => 'Requests policy by company and coverage'
        ],
        'contract' => [
            'class'       => Methods\Impl\LegalExpenses\ContractClient::class,
            'description' => 'Contract'
        ],
        'premium_extended'     => [
            'class'       => Methods\Impl\LegalExpenses\PremiumExtendedClient::class,
            'description' => 'Requests premium with extended coverages and inputs.',
        ],
        'coverages'     => [
            'class'       => Methods\Impl\LegalExpenses\CoverageItemListClient::class,
            'description' => 'Requests premiums, and get separated coverages.',
        ],
    ];

    protected $filterMapping = [
        self::COVERAGE_AREA            => 'filterUpperCaseFirst',
        self::COVERAGE_PERIOD          => 'filterNumber',
    ];
}