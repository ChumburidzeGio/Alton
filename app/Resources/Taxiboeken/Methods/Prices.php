<?php


namespace App\Resources\Taxiboeken\Methods;


use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Taxiboeken\TaxiboekenAbstractRequest;

class Prices extends TaxiboekenAbstractRequest
{
    protected $inputToExternalMapping = false;
    protected $externalToResultMapping = false;
    protected $inputTransformations = [
        //ResourceInterface::CATEGORY => 'implode',
    ];
    protected $resultKeyname = false;

    public function executeFunction()
    {
        $paramsTo = $this->params;

        if ($this->debug())
            $paramsTo['debug'] = 1;

        $paramsTo[ResourceInterface::DESTINATION_ARRIVAL_DATE] = $this->params[ResourceInterface::DESTINATION_DEPARTURE_DATE];

        $to = ResourceHelper::callResource2('single_ride_prices.taxiboeken', $paramsTo);

        if (!array_get($paramsTo, ResourceInterface::ONE_WAY, false)) {

            $paramsFrom = [];
            if ($this->debug())
                $paramsFrom['debug'] = 1;
            foreach ($this->params as $key => $value) {
                if (str_contains($key, 'destination') and !str_contains($key, '_date')) {
                    $paramsFrom[str_replace('destination', 'origin', $key)] = $value;
                } else if (str_contains($key, 'origin') and !str_contains($key, '_date')) {
                    $paramsFrom[str_replace('origin', 'destination', $key)] = $value;
                } else {
                    $paramsFrom[$key] = $value;
                }
            }

            $from = ResourceHelper::callResource2('single_ride_prices.taxiboeken', $paramsFrom);

            $rides = $this->mergeRides($to, $from);
        } else {
            $rides = $to;
        }

        $this->result = $rides;
    }

    protected function mergeRides($to, $from)
    {
        $rides = [];
        foreach ($to as $toRide)
        {
            foreach ($from as $fromRide)
            {
                if ($toRide[ResourceInterface::CATEGORY] == $fromRide[ResourceInterface::CATEGORY]) {
                    $ride = [];
                    $ride[ResourceInterface::CATEGORY] = $toRide[ResourceInterface::CATEGORY];
                    $ride[ResourceInterface::PRICE_ACTUAL] = $toRide[ResourceInterface::PRICE_ACTUAL] + $fromRide[ResourceInterface::PRICE_ACTUAL];
                    $ride[ResourceInterface::PASSENGERS_CAPACITY] = min($toRide[ResourceInterface::PASSENGERS_CAPACITY], $fromRide[ResourceInterface::PASSENGERS_CAPACITY]);
                    $ride['@unmapped']['to'] = $toRide;
                    $ride['@unmapped']['from'] = $fromRide;
                    $rides[] = $ride;
                }
            }
        }

        return $rides;
    }
}