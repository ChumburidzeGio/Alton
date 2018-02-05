<?php

namespace App\Resources\Parking2;


use App\Resources\AbstractServiceRequest;

class Parking extends AbstractServiceRequest
{
    const RESERVATION_STATUS_PENDING = 'pending';       // Created (but not paid)
    const RESERVATION_STATUS_COMPLETED = 'completed';   // Created and paid (and external API called)
    const RESERVATION_STATUS_CANCELED = 'canceled';     // Created, and then canceled
    const RESERVATION_STATUS_ERROR = 'error';           // Unrecoverable error during process

    protected $methodMapping = [
        'products' => [
            'class'       => \App\Resources\Parking2\Methods\Products::class,
            'description' => 'Get all products from here',
        ],
        'package' => [
            'class'       => \App\Resources\Parking2\Methods\Package::class,
            'description' => 'Get all products plus taxis and other stuff',
        ],
        'areas'    => [
            'class'       => \App\Resources\Parking2\Methods\Areas::class,
            'description' => 'Get all areas from all data sources.',
        ],
        'options'    => [
            'class'       => \App\Resources\Parking2\Methods\Options::class,
            'description' => 'Get all options from all data sources.',
        ],
        'services'    => [
            'class'       => \App\Resources\Parking2\Methods\Services::class,
            'description' => 'Get all servers from all data sources.',
        ],
        'contract'    => [
            'class'       => \App\Resources\Parking2\Methods\Contract::class,
            'description' => 'Create contract',
        ],
        'startpayment'    => [
            'class'       => \App\Resources\Parking2\Methods\StartPayment::class,
            'description' => 'Pay for a reservation',
        ],
        'updatepaymentstatus'    => [
            'class'       => \App\Resources\Parking2\Methods\UpdatePaymentStatus::class,
            'description' => 'Update the payment  status for a reservation (and do reservation calls)',
        ],
    ];

    public function getSyncableMethods() {
        return array_keys($this->methodMapping);
    }
}