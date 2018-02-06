<?php
namespace App\Resources\Healthcarech;


use App\Resources\AbstractServiceRequest;

class Healthcarech extends AbstractServiceRequest
{
    protected $cacheDays = false;

    protected $methodMapping = [
        'products'            => [
            'class'       => \App\Resources\Healthcarech\Methods\Products::class,
            'description' => 'Loads list of products'
        ],
        'companies'           => [
            'class'       => \App\Resources\Healthcarech\Methods\Companies::class,
            'description' => 'Loads list of companies'
        ],
        'hmos'           => [
            'class'       => \App\Resources\Healthcarech\Methods\Hmos::class,
            'description' => 'Loads list of hmos with addresses'
        ],
        'communes'           => [
            'class'       => \App\Resources\Healthcarech\Methods\Communes::class,
            'description' => 'Loads list of communes with postalcodes'
        ],
        'regions'             => [
            'class'       => \App\Resources\Healthcarech\Methods\Regions::class,
            'description' => 'Load list of kanton/regions'
        ],
        'insurancecompanies'  => [
            'class'       => \App\Resources\Healthcarech\Methods\InsuranceCompanies::class,
            'description' => 'Load list of Insurance Companies'
        ],
        'contract'            => [
            'class'       => \App\Resources\Healthcarech\Methods\Contract::class,
            'description' => 'Set up a contract'
        ],
        'requestverification' => [
            'class'       => \App\Resources\Healthcarech\Methods\RequestVerification::class,
            'description' => 'Get extra sms'
        ],
        'verifycontract' => [
            'class'       => \App\Resources\Healthcarech\Methods\VerifyContract::class,
            'description' => 'Verify contract with code'
        ],
        'additional' => [
            'class'       => \App\Resources\Healthcarech\Methods\Additional::class,
            'description' => 'Add additional insurances'
        ],
        'transfercontracts' => [
            'class'       => \App\Resources\Healthcarech\Methods\TransferContracts::class,
            'description' => 'Add additional insurances'
        ],
    ];
}
