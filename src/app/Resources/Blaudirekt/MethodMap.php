<?php

namespace App\Resources\Blaudirekt;

use App\Resources\AbstractServiceRequest;

class MethodMap extends AbstractServiceRequest
{
    protected $methodMapping = [
        'products_privateliabilityde'          => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\ProductPrivateliabilityde::class,
            'description' => 'Get the List of the Products with the ID of the Insurance Company (in german "Gesellschaft").',
        ],
        'premium_privateliabilityde'          => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\PremiumPrivateliabilityde::class,
            'description' => '',
        ],
        'IndexProductPrivateliabilityde' => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\IndexProductPrivateliabilityde::class,
            'description' => '',
        ],
        'contract_privateliabilityde' => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\ContractPrivateliabilityde::class,
            'description' => '',
        ],
        'getkunde_privateliabilityde' => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\GetKundePrivateliabilityde::class,
            'description' => '',
        ],
        'getaddressen_privateliabilityde' => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\GetAdressenPrivateliabilityde::class,
            'description' => '',
        ],
        'getkontaktdaten_privateliabilityde' => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\GetKontaktdatenPrivateliabilityde::class,
            'description' => '',
        ],
        'getvertraege_privateliabilityde' => [
            'class'       => \App\Resources\Blaudirekt\Methods\Privateliabilityde\GetVertraegePrivateliabilityde::class,
            'description' => '',
        ],
    ];
}