<?php

namespace App\Resources\Zorgweb;

use App\Resources\AbstractServiceRequest;

class HealthcareLegacy extends AbstractServiceRequest
{

    public $skipDefaultFields = true;

    protected $methodMapping = [
        'contract'     => [
            'class'       => \App\Resources\Zorgweb\Methods\Contract::class,
            'description' => 'Get contract'
        ],
        'contractfields'     => [
            'class'       => \App\Resources\Zorgweb\Methods\ContractFields::class,
            'description' => 'Get contract contract fields'
        ],
        'createfieldcache'     => [
            'class'       => \App\Resources\Zorgweb\Methods\CreateFieldCache::class,
            'description' => 'Create contract arguments'
        ],

    ];

    protected $filterKeyMapping = [
        self::POSTAL_CODE  => 'hoofdadres.postcode',
        self::HOUSE_NUMBER => 'hoofdadres.huisnummer',
        self::STREET       => 'hoofdadres.straat',
        self::CITY         => 'hoofdadres.woonplaats',
    ];

}


?>