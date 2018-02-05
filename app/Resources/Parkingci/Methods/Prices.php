<?php
namespace App\Resources\Parkingci\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Parkingci\ParkingciAbstractRequest;

class Prices extends ParkingciAbstractRequest
{
    protected $cacheDays = false;

    protected $inputTransformations = [
        ResourceInterface::ARRIVAL_DATE     => 'formatDateTime',
        ResourceInterface::DEPARTURE_DATE   => 'formatDateTime',
        ResourceInterface::AIRPORT_ID       => 'convertAirportId',
        ResourceInterface::OPTIONS          => 'splitCommaSeparated',
        ResourceInterface::SERVICES         => 'splitCommaSeparated',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::ARRIVAL_DATE     => 'arrival',
        ResourceInterface::DEPARTURE_DATE   => 'departure',
        ResourceInterface::AIRPORT_ID       => 'location',
        ResourceInterface::OPTIONS          => 'options',
        ResourceInterface::SERVICES         => 'service',
        ResourceInterface::SOURCE           => 'source',
        ResourceInterface::COSTFREE_CANCELLATION => 'costfree_cancellation',
        ResourceInterface::PARKING_ID       => 'park_id',
        ResourceInterface::USER             => 'par_id',
    ];
    protected $externalToResultMapping = [
        'park_id'       => ResourceInterface::LOCATION_ID,
        'ins_name'      => ResourceInterface::NAME,
        'price_topay'   => ResourceInterface::PRICE_ACTUAL,
        'park_location' => ResourceInterface::AREA_ID,
        'options'       => ResourceInterface::OPTIONS,
        'available_options' => ResourceInterface::AVAILABLE_OPTIONS,
        'park_availability' => ResourceInterface::AVAILABILITY_COUNT,
        'park_nachttoeslag' => ResourceInterface::NIGHT_SURCHARGE,
        'price_nacht'   => ResourceInterface::PRICE_NIGHT_SURCHARGE,
        'source'        => ResourceInterface::SOURCE,
        'price_costfree_cancellation' => ResourceInterface::PRICE_COSTFREE_CANCELLATION,
        'price_administration_fee' => ResourceInterface::PRICE_ADMINISTRATION_FEE,
    ];
    protected $resultTransformations = [
        ResourceInterface::IS_UNAVAILABLE => 'getIsUnavailable',
        ResourceInterface::NIGHT_SURCHARGE => 'floatval',
        ResourceInterface::PRICE_ADMINISTRATION_FEE => 'floatval',
        ResourceInterface::PRICE_COSTFREE_CANCELLATION => 'floatval',
        ResourceInterface::AVAILABILITY_COUNT => 'intval',
        ResourceInterface::AVAILABLE_OPTIONS => 'splitCommaSeparated',
    ];

    public function __construct()
    {
        parent::__construct('results');
    }

    public function setParams(array $params)
    {
        // Array in array? It is the odd mapping from & to for multiple products. Ignore.
        if (isset($params[ResourceInterface::OPTIONS])) {
            foreach ((array)$params[ResourceInterface::OPTIONS] as $option) {
                if (is_array($option)) {
                    $params[ResourceInterface::OPTIONS] = [];
                    break;
                }
            }
            $params[ResourceInterface::OPTIONS] = array_unique($params[ResourceInterface::OPTIONS]);
        }

        // For now, only map for the 'Vliegenenparkeren' user, which seems to have more access that the default '121'
        if (isset($params[ResourceInterface::USER]) && $params[ResourceInterface::USER] != '2329')
            unset($params[ResourceInterface::USER]);

        // Resource 2 mapping handling
        // If only one resource type is requested, pass it as a filter
        if (isset($params[ResourceInterface::SOURCE]) && is_array($params[ResourceInterface::SOURCE])) {
            $params[ResourceInterface::SOURCE] = array_unique($params[ResourceInterface::SOURCE]);
            if (count($params[ResourceInterface::SOURCE]) == 0)
                $params[ResourceInterface::SOURCE] = head($params[ResourceInterface::SOURCE]);
            else
                unset($params[ResourceInterface::SOURCE]);
        }
        // If only one location is requested, add it as a filter
        if (isset($params[ResourceInterface::LOCATION_ID]) && is_array($params[ResourceInterface::LOCATION_ID])) {
            $params[ResourceInterface::LOCATION_ID] = array_unique($params[ResourceInterface::LOCATION_ID]);
            if (count($params[ResourceInterface::LOCATION_ID]) == 1)
                $params[ResourceInterface::LOCATION_ID] = head($params[ResourceInterface::LOCATION_ID]);
            else
                unset($params[ResourceInterface::LOCATION_ID]);
        }

        if (isset($params[ResourceInterface::PARKING_ID]) && is_array($params[ResourceInterface::PARKING_ID])) {
            $params[ResourceInterface::PARKING_ID] = implode(',', $params[ResourceInterface::PARKING_ID]);
        }

        parent::setParams($params);
    }

    protected function getIsUnavailable($value)
    {
        return false; // All parkings we get prices for are available.
    }

    protected function getDefaultParams()
    {
        // These are required, else we get a PHP error from the API
        return [
            'arrival' => '',
            'departure' => '',
            'location' => '0', // '0' means all locations
            'show_sources' => '1', // Magickal parameter to get the potentially sensitive 'source' data
            'costfree_cancellation' => '0',
        ];
    }

    protected function convertAirportId($value)
    {
        if (is_array($value))
        {
            $value = array_unique($value);
            if (count($value) == 1)
                return $value[0];

            return 0; // '0' means: get all airports
        }

        if (!$value)
            return 0;

        return $value;
    }

    protected function splitCommaSeparated($value)
    {
        if (is_array($value))
            return $value;

        if ($value == '')
            return [];

        return explode(',', $value);
    }

    protected function floatval($value)
    {
        return floatval($value);
    }

    protected function intval($value)
    {
        return intval($value);
    }

    public function getResult()
    {
        if (isset($this->result['status']) && $this->result['status'] == 'error' && $this->result['message'] == 'No results found.') {
            return [];
        }

        return parent::getResult();
    }
}