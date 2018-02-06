<?php
/**
 * User: Roeland Werring
 * Date: 13/05/15
 * Time: 10:50
 *
 */
namespace App\Resources\General;

use App\Resources\AbstractServiceRequest;

class General extends AbstractServiceRequest
{
    protected $filterKeyMapping = [
        self::POSTAL_CODE  => 'adres_postcode',
        self::HOUSE_NUMBER => 'adres_huisnummer',
        self::SUFFIX       => 'adres_toevoeging'
    ];


    protected $methodMapping = [
        'address' => [
            'class'       => \App\Resources\Easyswitch\Methods\Impl\EnergyLocations::class,
            'description' => 'Request a list of locations based on postalcode and house number'
        ],
        'company' => [
            'class'       => \App\Resources\General\Methods\GetCompanies::class,
            'description' => 'Request a list of unique companies based on product type, website and/or user'
        ],
        'storage' => [
            'class'       => \App\Resources\General\Methods\Storage::class,
            'description' => 'Allow to upload and receive files from API'
        ],
        'limited_access_token' => [
            'class'       => \App\Resources\General\Methods\LimitedAccessToken::class,
            'description' => 'Get one time access via an encrypted token.'
        ],
        'notify_applications' => [
            'class'       => \App\Resources\General\Methods\NotifyApplications::class,
            'description' => 'Send notifications of events to registered applications with callbacks.'
        ],
    ];
}
