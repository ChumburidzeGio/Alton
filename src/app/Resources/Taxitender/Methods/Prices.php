<?php


namespace App\Resources\Taxitender\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Taxitender\TaxitenderAbstractRequest;
use Illuminate\Support\Facades\App;

class Prices extends TaxitenderAbstractRequest
{
    protected $inputToExternalMapping = false;
    protected $externalToResultMapping = false;
    protected $inputTransformations = [
        ResourceInterface::CATEGORY => 'implode',
    ];
    protected $resultKeyname = false;


    public function setParams(array $params)
    {
        // taxitender has its own serviceLocations to and from which it can only pickup peoplez
        // we're finding the matching airport by simply (not really) giving similarity weights to these locations

        $serviceLocations = array_map(function ($serviceLocation) use ($params) {
            if (isset($params[ResourceInterface::DESTINATION_POINT_OF_INTEREST])) {
                $serviceLocation['destination_similarity'] = similar_text(
                    str_ireplace('airport', '', $params[ResourceInterface::DESTINATION_POINT_OF_INTEREST]),
                    str_ireplace('airport', '', $serviceLocation['name'])
                );
            }

            if (isset($params[ResourceInterface::ORIGIN_POINT_OF_INTEREST])) {
                $serviceLocation['origin_similarity'] = similar_text(
                    str_ireplace('airport', '', $params[ResourceInterface::ORIGIN_POINT_OF_INTEREST]),
                    str_ireplace('airport', '', $serviceLocation['name'])
                );
            }

            return $serviceLocation;
        }, $this->internalRequest('taxitender', 'retrieve_service_locations'));

        if (isset($params[ResourceInterface::DESTINATION_POINT_OF_INTEREST])) {
            usort($serviceLocations, function ($a, $b) {
                return $b['destination_similarity'] - $a['destination_similarity'];
            });

            $destination_servicelocation = reset($serviceLocations);
            if ($destination_servicelocation['destination_similarity'] >= 8) {
                $params[ResourceInterface::DESTINATION_CODE] = $destination_servicelocation[ResourceInterface::AIRPORT_CODE];
            }
        }

        if (isset($params[ResourceInterface::ORIGIN_POINT_OF_INTEREST])) {
            usort($serviceLocations, function ($a, $b) {
                return $b['origin_similarity'] - $a['origin_similarity'];
            });

            $origin_servicelocation = reset($serviceLocations);
            if ($origin_servicelocation['origin_similarity'] >= 8) {
                $params[ResourceInterface::ORIGIN_CODE] = $origin_servicelocation[ResourceInterface::AIRPORT_CODE];
            }
        }

        if (!isset($params[ResourceInterface::LANGUAGE]))
            $params[ResourceInterface::LANGUAGE] = strtoupper(substr(App::getLocale(), 0, 2));

        if (!in_array($params[ResourceInterface::LANGUAGE], $this->supportedLanguages))
            $params[ResourceInterface::LANGUAGE] = 'EN';

        parent::setParams($params);

    }

    public function executeFunction()
    {
        $paramsTo = $this->params;

        if ($this->debug())
            $paramsTo['debug'] = 1;

        $paramsTo[ResourceInterface::DESTINATION_ARRIVAL_DATE] = $this->params[ResourceInterface::DESTINATION_DEPARTURE_DATE];

        $to = $this->internalRequest('taxitender', 'find_bookable_rides', $paramsTo, true);

        if ($this->resultHasError($to)) {
            $this->setErrorString('Ride to destination error: ' . json_encode($to));

            return [];
        }

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

            $from = $this->internalRequest('taxitender', 'find_bookable_rides', $paramsFrom, true);

            if ($this->resultHasError($from)) {
                $this->setErrorString('Ride from destination error: ' . json_encode($from));

                return [];
            }

            $rides = self::mergeClasses($to, $from);
        } else {
            $rides = $to;
        }

        foreach ($rides as $nr => $ride)
            $rides[$nr][ResourceInterface::RESERVATION_KEY] = base64_encode($ride[ResourceInterface::SEARCH_QUERY_ID] . '_' . $ride[ResourceInterface::SEARCH_QUERY_RESULT_ID]);

        $this->result = $rides;
    }

    private static function mergeClasses($to, $from)
    {
        $to         = self::indexRides($to);
        $from       = self::indexRides($from);
        $returnData = [];
        foreach (array_intersect(array_keys($to), array_keys($from)) as $class) {
            $returnData[] = TaxitenderAbstractRequest::mergeRides($to[$class], $from[$class]);
        }

        return $returnData;
    }

    private static function indexRides($rides)
    {
        return array_combine(array_map(function ($ride) {
            return $ride[ResourceInterface::CATEGORY];
        }, $rides), $rides);
    }
}