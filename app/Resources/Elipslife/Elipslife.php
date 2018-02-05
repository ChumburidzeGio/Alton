<?php
/**
 * User: kristian
 * Date: 30/10/2017
 * Time: 09:29
 *
 */

namespace App\Resources\Elipslife;

use App\Resources\AbstractServiceRequest;

class Elipslife extends AbstractServiceRequest
{
    protected $methodMapping = [
        'contract'     => [
            'class'       => \App\Resources\Elipslife\Methods\Contract::class,
            'description' => 'Create order from contract ?'
        ],
        'validatecontract'     => [
            'class'       => \App\Resources\Elipslife\Methods\ValidateContract::class,
            'description' => 'Validates if a contract can be made'
        ],
        'bmi_listing'     => [
            'class'       => \App\Resources\Elipslife\Methods\BmiListing::class,
            'description' => 'Get the bmi pass of fail listings'
        ],
    ];
}