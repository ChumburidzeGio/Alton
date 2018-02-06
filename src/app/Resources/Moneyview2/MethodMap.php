<?php
/**
 * Created by PhpStorm.
 * User: giorgi
 * Date: 11/22/17
 * Time: 10:27 AM
 */

namespace App\Resources\Moneyview2;

use App\Resources\AbstractServiceRequest;

class MethodMap extends AbstractServiceRequest
{
    protected $methodMapping = [

        //Content insurance
        'productlist_contentinsurance'          => [
            'class' => Methods\ContentInsurance\ProductList::class,
        ],
        'choicelist_contentinsurance'          => [
            'class' => Methods\ContentInsurance\ChoiceList::class,
        ],
        'premium_contentinsurance'          => [
            'class' => Methods\ContentInsurance\Premium::class,
        ],
        'policy_contentinsurance'          => [
            'class' => Methods\ContentInsurance\Policy::class,
        ],


        //Liability insurance
        'productlist_liabilityinsurance'          => [
            'class' => Methods\LiabilityInsurance\ProductList::class,
        ],
        'choicelist_liabilityinsurance'          => [
            'class' => Methods\LiabilityInsurance\ChoiceList::class,
        ],
        'premium_liabilityinsurance'          => [
            'class' => Methods\LiabilityInsurance\Premium::class,
        ],
        'policy_liabilityinsurance'          => [
            'class' => Methods\LiabilityInsurance\Policy::class,
        ],


        //Home insurance
        'productlist_homeinsurance'          => [
            'class' => Methods\HomeInsurance\ProductList::class,
        ],
        'choicelist_homeinsurance'          => [
            'class' => Methods\HomeInsurance\ChoiceList::class,
        ],
        'premium_homeinsurance'          => [
            'class' => Methods\HomeInsurance\Premium::class,
        ],
        'policy_homeinsurance'          => [
            'class' => Methods\HomeInsurance\Policy::class,
        ],


        //Travel Insurance
        'productlist_travelinsurance'          => [
            'class' => Methods\TravelInsurance\ProductList::class,
        ],
        'choicelist_travelinsurance'          => [
            'class' => Methods\TravelInsurance\ChoiceList::class,
        ],
        'premium_travelinsurance'          => [
            'class' => Methods\TravelInsurance\Premium::class,
        ],
        'policy_travelinsurance'          => [
            'class' => Methods\TravelInsurance\Policy::class,
        ],


        //Legal Expenses Insurance
        'productlist_legalexpensesinsurance'          => [
            'class' => Methods\LegalExpensesInsurance\ProductList::class,
        ],
        'choicelist_legalexpensesinsurance'          => [
            'class' => Methods\LegalExpensesInsurance\ChoiceList::class,
        ],
        'premium_legalexpensesinsurance'          => [
            'class' => Methods\LegalExpensesInsurance\Premium::class,
        ],
        'policy_legalexpensesinsurance'          => [
            'class' => Methods\LegalExpensesInsurance\Policy::class,
        ],
    ];
}