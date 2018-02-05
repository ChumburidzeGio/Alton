<?php

namespace App\Resources\Travel;


use App\Resources\AbstractServiceRequest;

class Travel extends AbstractServiceRequest
{
    const RESERVATION_STATUS_PENDING = 'pending';       // Created (but not paid)
    const RESERVATION_STATUS_COMPLETED = 'completed';   // Created and paid (and external API called)
    const RESERVATION_STATUS_CANCELED = 'canceled';     // Created, and then canceled
    const RESERVATION_STATUS_ERROR = 'error';           // Unrecoverable error during process

    protected $methodMapping = [
        'get_parkingci_products' => [
            'class'       => \App\Resources\Travel\Methods\GetParkingciProducts::class,
            'description' => 'Get all products from here',
        ],
        'get_parkingci_locations'    => [
            'class'       => \App\Resources\Travel\Methods\GetParkingciLocations::class,
            'description' => 'Get parking CI airports as locations.',
        ],
        'get_parkingci_options'    => [
            'class'       => \App\Resources\Travel\Methods\GetParkingciOptions::class,
            'description' => 'Get all options from all data sources.',
        ],
        'get_parkingci_services'    => [
            'class'       => \App\Resources\Travel\Methods\GetParkingciServices::class,
            'description' => 'Get all servers from all data sources.',
        ],
        'contract'    => [
            'class'       => \App\Resources\Travel\Methods\Contract::class,
            'description' => 'Create contract',
        ],
        'startpayment'    => [
            'class'       => \App\Resources\Travel\Methods\StartPayment::class,
            'description' => 'Pay for a reservation',
        ],
        'updatepaymentstatus'    => [
            'class'       => \App\Resources\Travel\Methods\UpdatePaymentStatus::class,
            'description' => 'Update the payment  status for a reservation (and do reservation calls)',
        ],
        'cancel_order'    => [
            'class'       => \App\Resources\Travel\Methods\CancelOrder::class,
            'description' => 'Cancel an order',
        ],
        'rebook_order'    => [
            'class'       => \App\Resources\Travel\Methods\RebookOrder::class,
            'description' => 'Rebook an order',
        ],
        'order_options'    => [
            'class'       => \App\Resources\Travel\Methods\OrderOptions::class,
            'description' => 'Get the available options for an order',
        ],
        'set_managing_user'    => [
            'class'       => \App\Resources\Travel\Methods\SetManagingUser::class,
            'description' => 'Set the managing user for a reseller/provider',
        ],
        'IndexUserRights'    => [
            'class'       => \App\Resources\Travel\Methods\IndexUserRights::class,
            'description' => 'Get a listing of rights for travel users.',
        ],
        'IndexWebsiteRights'    => [
            'class'       => \App\Resources\Travel\Methods\IndexWebsiteRights::class,
            'description' => 'Get a listing of rights for travel websites.',
        ],
        'ShowUserRights'    => [
            'class'       => \App\Resources\Travel\Methods\ShowUserRights::class,
            'description' => 'Get a right for travel websites based on id.',
        ],
        'UpdateUserRights'    => [
            'class'       => \App\Resources\Travel\Methods\UpdateUserRights::class,
            'description' => 'Update a right for travel websites based on id.',
        ],
        'StoreUserRights'    => [
            'class'       => \App\Resources\Travel\Methods\StoreUserRights::class,
            'description' => 'Update a right for travel websites based on id.',
        ],
        'ShowWebsiteRights'    => [
            'class'       => \App\Resources\Travel\Methods\ShowWebsiteRights::class,
            'description' => 'Get a right for travel websites based on id.',
        ],
        'UpdateWebsiteRights'    => [
            'class'       => \App\Resources\Travel\Methods\UpdateWebsiteRights::class,
            'description' => 'Update a right for travel websites based on id.',
        ],
        'DestroyRights'    => [
            'class'       => \App\Resources\Travel\Methods\DestroyRights::class,
            'description' => 'Delete a right for travel websites based on id.',
        ],
        'generate_openapi'    => [
            'class'       => \App\Resources\Travel\Methods\GenerateOpenAPI::class,
            'description' => 'Generate OpenAPI definition for external Travel API.',
        ],

        'StoreOrder'    => [
            'class'       => \App\Resources\Travel\Methods\StoreOrder::class,
            'description' => 'Store an order',
        ],
        'IndexOrder'    => [
            'class'       => \App\Resources\Travel\Methods\IndexOrder::class,
            'description' => 'Get orders',
        ],
        'ShowOrder'    => [
            'class'       => \App\Resources\Travel\Methods\ShowOrder::class,
            'description' => 'Show an order',
        ],
        'UpdateOrder'    => [
            'class'       => \App\Resources\Travel\Methods\UpdateOrder::class,
            'description' => 'Update an order',
        ],
        'IndexProductListing'    => [
            'class'       => \App\Resources\Travel\Methods\IndexProductListing::class,
            'description' => 'Show available products',
        ],
        'ShowProductListing'    => [
            'class'       => \App\Resources\Travel\Methods\ShowProductListing::class,
            'description' => 'Show available products',
        ],

    ];

    public function getSyncableMethods() {
        return array_keys($this->methodMapping);
    }
}