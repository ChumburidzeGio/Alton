<?php
namespace App\Resources\Parkingpro\Methods;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;
use Illuminate\Support\Facades\Log;

class Prices extends ParkingproAbstractRequest
{
    protected $inputTransformations = [
        ResourceInterface::ARRIVAL_DATE     => 'formatDateTime',
        ResourceInterface::DEPARTURE_DATE   => 'formatDateTime',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::ARRIVAL_DATE     => 'parkingDate',
        ResourceInterface::DEPARTURE_DATE   => 'returnDate',
    ];
    protected $externalToResultMapping = [
        'parkingId'  => ResourceInterface::PARKING_ID,
        'locationId'  => ResourceInterface::LOCATION_ID,
        'locationName'  => ResourceInterface::NAME,
        'calculation.totalWithTax'  => ResourceInterface::PRICE_ACTUAL,
        'calculation.isUnavailable' => ResourceInterface::IS_UNAVAILABLE,
    ];

    protected $singlePrice = null;

    public function __construct()
    {
        // Note: 'bulkprice' method does not work on Parking Pro test environment
        // switch to Parking Pro production environment to work with this method
        parent::__construct('bulkprice');
    }

    public function getResult()
    {
        $result = parent::getResult();

        $result = $this->addOptions($result);

        $requestedOptions = $this->castToStringArray(array_get($this->inputParams, ResourceInterface::OPTIONS));
        if ($requestedOptions)
            $result = $this->filterOptions($result, $requestedOptions);

        return $result;
    }

    /**
     * Because the 'bulkprice' method does not support showing available options, we do it ourselves by calling the 'options.parkingpro' function.
     */
    protected function addOptions($locations)
    {
       // We cannot request options from unavailable parkings
        $allAvailableParkings = array_filter(array_map(function ($p) {
            return isset($p[ResourceInterface::IS_UNAVAILABLE]) && $p[ResourceInterface::IS_UNAVAILABLE] ? null : $p[ResourceInterface::PARKING_ID];
        }, ResourceHelper::callResource2('parkings.parkingpro')));

        $parkingIds = array_pluck($locations, ResourceInterface::PARKING_ID);

        // Fetch all option-data (these are each cached for an hour)
        $parkingOptions = [];
        foreach ($allAvailableParkings as $parkingId) {
            if (!in_array($parkingId, $parkingIds))
                continue;

            try {
                $parkingOptions[$parkingId] = ResourceHelper::callResource2('options.parkingpro', [ResourceInterface::LOCATION_ID => $parkingId . '|ALL_LOCATIONS']);
            } catch (\Exception $e) {
                Log::warning('Error requesting options: ' . (string)$e .' - input: '. json_encode($this->inputParams));
            }
        }

        // See if our locations have the options, and apply their cost data.
        foreach ($locations as $key => $location) {
            $locations[$key][ResourceInterface::PRICE_OPTIONS] = 0;
            $locations[$key][ResourceInterface::PRODUCT_OPTIONS] = [];

            if (!isset($parkingOptions[$location[ResourceInterface::PARKING_ID]]))
                continue;

            foreach ($parkingOptions[$location[ResourceInterface::PARKING_ID]] as $parkingOption) {
                if (!in_array($location[ResourceInterface::LOCATION_ID], $parkingOption[ResourceInterface::LOCATIONS]))
                    continue;

                if ($parkingOption[ResourceInterface::IS_UNAVAILABLE])
                    continue;

                // We don't need this field anymore
                unset($parkingOption[ResourceInterface::LOCATIONS]);

                $locations[$key][ResourceInterface::PRODUCT_OPTIONS][] = $parkingOption;
            }
        }

        return $locations;

    }

    /**
     * Filtering on which options are available, can be done via simple Parking Pro option ids (single hash),
     * or specifying options for specific locations (via very long 'parking_id|location_id|option_id')
     */
    protected function filterOptions($locations, $requestedOptions)
    {
        // Check if we have any options specified as 'parking_id|location_id|option_id'
        $requestedLocationOptions = [];
        foreach ($requestedOptions as $k => $requestedOption) {
            if (str_contains($requestedOption, '|')) {
                list($p, $l, $o) = explode('|', $requestedOption);
                $requestedLocationOptions[$p .'|'. $l][] = $o;
                $requestedOptions[$k] = $o;
            }
        }

        // See if our locations have the options, and add their price data.
        $filteredLocations = [];
        foreach ($locations as $location) {
            if ($requestedLocationOptions !== [] && !isset($requestedLocationOptions[$location[ResourceInterface::LOCATION_ID]]))
                continue;

            $location[ResourceInterface::PRICE_OPTIONS] = 0;
            $optionsFound = 0;
            foreach ($location[ResourceInterface::PRODUCT_OPTIONS] as $parkingOption) {
                if ($parkingOption[ResourceInterface::IS_UNAVAILABLE])
                    continue;

                if (isset($requestedLocationOptions[$location[ResourceInterface::LOCATION_ID]]) && in_array($parkingOption[ResourceInterface::OPTION_ID], $requestedLocationOptions[$location[ResourceInterface::LOCATION_ID]])) {
                    $location[ResourceInterface::PRICE_ACTUAL] += $parkingOption[ResourceInterface::PRICE_ACTUAL];
                    $location[ResourceInterface::PRICE_OPTIONS] += $parkingOption[ResourceInterface::PRICE_ACTUAL];
                    $optionsFound++;
                }
                else if (in_array($parkingOption[ResourceInterface::OPTION_ID], $requestedOptions)) {
                    $location[ResourceInterface::PRICE_ACTUAL] += $parkingOption[ResourceInterface::PRICE_ACTUAL];
                    $location[ResourceInterface::PRICE_OPTIONS] += $parkingOption[ResourceInterface::PRICE_ACTUAL];
                    $optionsFound++;
                }
            }

            // Todo: We do not know which options are intended for each price. So we assume if any option is found, it is ok.
            if ($requestedLocationOptions === [] && $optionsFound == 0)
                continue;

            // Check if we are missing location-specific options
            if ($requestedLocationOptions && !isset($requestedLocationOptions[$location[ResourceInterface::LOCATION_ID]]))
                continue;
            if ($requestedLocationOptions && $optionsFound !== count($requestedLocationOptions[$location[ResourceInterface::LOCATION_ID]]))
                continue;

            $filteredLocations[] = $location;
        }

        return $filteredLocations;
    }

    public function getDefaultParams()
    {
        return [
            'parkingDate' => null,
            'returnDate' => null,
            'passengerCount' => null,
        ];
    }

    public function castToStringArray($value)
    {
        if (!is_array($value)) {
            if ($value === 0 || $value === null)
                $value = [];
            else
                $value = [(string)$value];
        }

        foreach ($value as $k => $v) {
            $value[$k] = (string)$v;
        }

        return $value;
    }
}