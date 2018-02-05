<?php


namespace App\Resources\Taxiboeken\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxiboeken\TaxiboekenAbstractRequest;

class SingleRidePrices extends TaxiboekenAbstractRequest
{
    protected $inputTransformations = [
        ResourceInterface::DEPARTURE_DATE => 'formatDateTime',
        ResourceInterface::CATEGORY       => 'explode',
    ];

    protected $inputToExternalMapping = [
        ResourceInterface::COMPANIES                     => 'companies',
        ResourceInterface::CATEGORY                      => 'vehicleTypes',
        ResourceInterface::DEPARTURE_DATE                => 'requestedDate',
        ResourceInterface::PASSENGERS                    => 'passengerCount',
        ResourceInterface::ORIGIN_CITY                   => 'departure.city',
        ResourceInterface::ORIGIN_STREET                 => 'departure.streetName',
        ResourceInterface::ORIGIN_POSTAL_CODE            => 'departure.postalCode',
        ResourceInterface::ORIGIN_HOUSE_NUMBER           => 'departure.houseNumber',
        ResourceInterface::ORIGIN_LATITUDE               => 'departure.gps.lat',
        ResourceInterface::ORIGIN_LONGITUDE              => 'departure.gps.lng',
        ResourceInterface::ORIGIN_COUNTRY_CODE           => 'departure.countryCode',
        ResourceInterface::ORIGIN_POINT_OF_INTEREST      => 'departure.internationalAlias',
        ResourceInterface::DESTINATION_CITY              => 'destination.city',
        ResourceInterface::DESTINATION_STREET            => 'destination.streetName',
        ResourceInterface::DESTINATION_POSTAL_CODE       => 'destination.postalCode',
        ResourceInterface::DESTINATION_HOUSE_NUMBER      => 'destination.houseNumber',
        ResourceInterface::DESTINATION_LATITUDE          => 'destination.gps.lat',
        ResourceInterface::DESTINATION_LONGITUDE         => 'destination.gps.lng',
        ResourceInterface::DESTINATION_COUNTRY_CODE      => 'destination.countryCode',
        ResourceInterface::DESTINATION_POINT_OF_INTEREST => 'destination.internationalAlias',
    ];

    protected $externalToResultMapping = [
        'type'          => ResourceInterface::CATEGORY,
        'maxPassengers' => ResourceInterface::PASSENGERS_CAPACITY,
        'price.total'   => ResourceInterface::PRICE_ACTUAL,
    ];

    protected $resultTransformations = [
        ResourceInterface::PRICE_ACTUAL => 'priceToDecimal',
    ];

    public function __construct()
    {
        parent::__construct('prices', self::METHOD_POST);
    }

    protected function getDefaultParams()
    {
        return [
            'passengerCount' => 2,
            'companies' => \((app()->configure('resource_taxiboeken')) ? '' : config('resource_taxiboeken.settings.companies')),
            'vehicleTypes' => ['saloon', 'estate', 'bus', 'minivan', 'limo'],
        ];
    }
}