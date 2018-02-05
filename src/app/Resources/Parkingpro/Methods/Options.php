<?php
namespace App\Resources\Parkingpro\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingpro\ParkingproAbstractRequest;
use Illuminate\Support\Facades\Cache;

class Options extends ParkingproAbstractRequest
{
    const ALL_LOCATIONS = 'ALL_LOCATIONS';

    protected $inputToExternalMapping = [
        ResourceInterface::PARKING_ID => 'parkingId',
    ];
    protected $externalToResultMapping = [
        'id'                => ResourceInterface::OPTION_ID,
        'title'             => ResourceInterface::NAME,
        'description'       => ResourceInterface::DESCRIPTION,
        'amount'            => ResourceInterface::PRICE_ACTUAL,
        'isUnavailable'     => ResourceInterface::IS_UNAVAILABLE,
        'locations'         => ResourceInterface::LOCATIONS,
    ];

    public function __construct()
    {
        parent::__construct('options');
    }

    public function executeFunction()
    {
        // Cache per parkingId
        $cacheKey = 'parkingpro-options-'. $this->parkingId;

        $this->result = Cache::get($cacheKey);

        if ($this->result === null || !empty($this->inputParams['debug'])) {
            parent::executeFunction();
            if (!$this->hasErrors()) {
                Cache::put($cacheKey, $this->result, rand(40, 60)); // Random minutes timeout, to 'stagger' cache timeouts
            }
        }
    }

    public function getResult()
    {
        if (!is_array($this->result))
        {
            if (!$this->hasErrors())
                $this->setErrorString('Unexpected non-array result.');
            return [];
        }

        // Two-deep structure
        $allOptions = [];

        foreach ($this->result as $optionType)
        {
            $allOptions = array_merge($allOptions, $optionType['options']);
        }

        // Insert parkingId, insert default fields
        foreach ($allOptions as $key => $option)
        {
            // Normalize locations
            $allOptions[$key]['locations'] = [];
            foreach ($allOptions[$key]['availableLocations'] as $location)
                $allOptions[$key]['locations'][] = $this->insertParkingIds($location, ['id'])['id'];
            sort($allOptions[$key]['locations']);
            unset($allOptions[$key]['availableLocations']);

            // All options are always available
            $allOptions[$key]['isUnavailable'] = false;
        }

        $result = $this->mapExternalToResult($allOptions);
        $result = $this->applyResultTransforms($result);

        if ($this->inputParams[ResourceInterface::LOCATION_ID] != $this->parkingId .'|'. self::ALL_LOCATIONS)
        {
            // Filter only those available in this location
            $locationOptions = [];
            foreach ($result as $option)
            {
                foreach ($option['locations'] as $locationId)
                {
                    if ($locationId == $this->inputParams[ResourceInterface::LOCATION_ID]) {
                        $option['locations'] = [$locationId];
                        $locationOptions[] = $option;
                    }
                }
            }
            return $locationOptions;
        }

        return $result;
    }
}