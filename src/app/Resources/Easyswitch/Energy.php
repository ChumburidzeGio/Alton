<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:29
 * 
 */

namespace App\Resources\Easyswitch;

class Energy extends EasyswitchServiceRequest
{
    protected $methodMapping = [
        'products'     => [
            'class'       => \App\Resources\Easyswitch\Methods\Impl\EnergyProductList::class,
            'description' => 'Request list of products'
        ],
        'locations'     => [
            'class'       => \App\Resources\Easyswitch\Methods\Impl\EnergyLocations::class,
            'description' => 'Request a list of locations based on postalcode and house number'
        ],
        'compare'     => [
            'class'       => \App\Resources\Easyswitch\Methods\Impl\EnergyCompare::class,
            'description' => 'Compare energy products based on various data. If contract_id or id\'s are provided, results will be eneriched with extra data'
        ],
        'contract'     => [
            'class'       => \App\Resources\Easyswitch\Methods\Impl\EnergyContract::class,
            'description' => 'Fill in and send off contract for this energy product'
        ],
        'details'     => [
            'class'       => \App\Resources\Easyswitch\Methods\Impl\EnergyDetails::class,
            'description' => 'Product details'
        ],
        'presets'     => [
            'class'       => \App\Resources\Easyswitch\Methods\Impl\EnergyPresets::class,
            'description' => 'Product details'
        ],
    ];
}