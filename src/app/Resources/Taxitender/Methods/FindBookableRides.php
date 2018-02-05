<?php


namespace App\Resources\Taxitender\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;
use Illuminate\Support\Facades\Config;

class FindBookableRides extends TaxitenderAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::DESTINATION_ARRIVAL_DATE => 'formatDateTimeUtc',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::DESTINATION_ARRIVAL_DATE          => 'pickupDatetime',
        ResourceInterface::ORIGIN_CODE                       => 'fromServiceLocation',
        ResourceInterface::ORIGIN_ADDRESS                    => 'fromAddress',
        ResourceInterface::ORIGIN_LATITUDE                   => 'fromLatitude',
        ResourceInterface::ORIGIN_LONGITUDE                  => 'fromLongitude',
        ResourceInterface::DESTINATION_CODE                  => 'toServiceLocation',
        ResourceInterface::DESTINATION_ADDRESS               => 'toAddress',
        ResourceInterface::DESTINATION_LATITUDE              => 'toLatitude',
        ResourceInterface::DESTINATION_LONGITUDE             => 'toLongitude',
        ResourceInterface::CATEGORY                          => 'filterVehicleCategory',
        ResourceInterface::PASSENGERS                        => 'passengers',
        ResourceInterface::LUGGAGE                           => 'luggage',
        ResourceInterface::LANGUAGE                          => 'languageCode',
    ];
    protected $externalToResultMapping = [
        'searchQueryID'            => ResourceInterface::SEARCH_QUERY_ID,
        'searchQueryResultID'      => ResourceInterface::SEARCH_QUERY_RESULT_ID,
        'vehicleTitle'             => ResourceInterface::TITLE,
        'vehicleExample'           => ResourceInterface::DESCRIPTION,
        'vehicleImage'             => ResourceInterface::IMAGE,
        'vehicleCategory'          => ResourceInterface::CATEGORY,
        'priceInclVat'             => ResourceInterface::PRICE_ACTUAL,
        'distance'                 => ResourceInterface::DISTANCE,
        'duration'                 => ResourceInterface::TIME,
        'taxiTenderLogo'           => ResourceInterface::BRAND_LOGO,
        'vehiclePassengerCapacity' => ResourceInterface::PASSENGERS_CAPACITY,
    ];
    protected $resultTransformations = [
        ResourceInterface::TIME => 'secondsToMinutes',
    ];

    public function __construct()
    {
        parent::__construct('findBookableRides');
    }

    protected function getDefaultParams()
    {
        return [
            'languageCode'    => ((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.default.languageCode')),
            'currencyIsoCode' => ((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.default.currencyIsoCode')),
            'passengers'      => 2,
        ];
    }

    public function setParams(array $params)
    {
        if (!isset($params[ResourceInterface::PASSENGERS])) {
            $params[ResourceInterface::PASSENGERS] = 2;
        }
        if (!isset($params[ResourceInterface::LUGGAGE])) {
            $params[ResourceInterface::LUGGAGE] = 2;
        }

        if (empty($params[ResourceInterface::ORIGIN_CODE])
            && empty($params[ResourceInterface::ORIGIN_ADDRESS])
            && empty($params[ResourceInterface::ORIGIN_LATITUDE])
            && empty($params[ResourceInterface::ORIGIN_LONGITUDE]))
        {
            $this->addErrorMessage(ResourceInterface::ORIGIN_ADDRESS, 'origin-unknown.taxitender', 'No origin specified for taxi ride.');
        }

        if (empty($params[ResourceInterface::DESTINATION_CODE])
            && empty($params[ResourceInterface::DESTINATION_ADDRESS])
            && empty($params[ResourceInterface::DESTINATION_LATITUDE])
            && empty($params[ResourceInterface::DESTINATION_LONGITUDE]))
        {
            $this->addErrorMessage(ResourceInterface::DESTINATION_ADDRESS, 'destination-unknown.taxitender', 'No destination specified for taxi ride.');
        }

        if (isset($params[ResourceInterface::ORIGIN_CODE])) {
            foreach(array_keys($params) as $key) {
                if (preg_match('/^origin_/i', $key) and $key != ResourceInterface::ORIGIN_CODE) {
                    unset($params[$key]);
                }
            }
        }
        if (isset($params[ResourceInterface::DESTINATION_CODE])) {
            foreach(array_keys($params) as $key) {
                if (preg_match('/^destination_/i', $key) && $key != ResourceInterface::DESTINATION_CODE && $key != ResourceInterface::DESTINATION_ARRIVAL_DATE) {
                    unset($params[$key]);
                }
            }
        }

        parent::setParams($params);
    }
}