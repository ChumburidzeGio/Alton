<?php
namespace App\Resources\Knip;


use App\Resources\AbstractServiceRequest;

class Knip extends AbstractServiceRequest
{
    protected $cacheDays = false;

    protected $methodMapping = [
        'ShowAccount' => [
            'class'       => \App\Resources\Knip\Methods\ShowAccount::class,
            'description' => 'Get an accounts information'
        ],
        'IndexAccount' => [
            'class'       => \App\Resources\Knip\Methods\IndexAccount::class,
            'description' => 'Get an account listing'
        ],
        'StoreAccount' => [
            'class'       => \App\Resources\Knip\Methods\StoreAccount::class,
            'description' => 'Store a new account'
        ],
        'get_account_hash' => [
            'class'       => \App\Resources\Knip\Methods\GetAccountKey::class,
            'description' => 'Get a new account hash'
        ],
        'create_account_hash' => [
            'class'       => \App\Resources\Knip\Methods\CreateAccountKey::class,
            'description' => 'Get a new account hash'
        ],
        'set_additional_insurances' => [
            'class'       => \App\Resources\Knip\Methods\SetSelectedAdditionalInsurance::class,
            'description' => 'Set the selected product company and price'
        ],
    ];
}
