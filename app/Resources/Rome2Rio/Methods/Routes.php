<?php

namespace App\Resources\Rome2Rio\Travel\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Rome2Rio\Travel\TravelAbstractRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class Routes extends TravelAbstractRequest
{
    const DEFAULT_CURRENCY = 'eur';
    const DEFAULT_LANGUAGE = 'en';
    const DEFAULT_DISPLAY_LANGUAGE = 'nl';
    const DEFAULT_ORDER_BY_PREDICATE = 'totalDuration';
    const ALLOW_AIR = false;
    const ALLOW_CAR = false;
    const ALLOW_RIDESHARE = false;

    protected $cacheDays = false;
    protected $clearUnmapped = false;

    protected $inputTransformations = [];

    protected $inputGenerators = [
        ResourceInterface::COMPOUND_ORIGIN_COORDINATES => 'mergeOriginCoordinates',
        ResourceInterface::COMPOUND_DESTINATION_COORDINATES => 'mergeDestinationCoordinates',
        ResourceInterface::LANGUAGE => 'generateLanguageCode',
        ResourceInterface::CURRENCY => 'generateCurrencyCode',
        'multiple_keys'             => 'generateConstants',
    ];

    protected $inputToExternalMapping = [
        ResourceInterface::ORIGIN_ADDRESS => 'oName',
        ResourceInterface::DESTINATION_ADDRESS => 'dName',
        ResourceInterface::COMPOUND_ORIGIN_COORDINATES => 'oPos',
        ResourceInterface::COMPOUND_DESTINATION_COORDINATES => 'dPos',
        ResourceInterface::LANGUAGE => 'languageCode',
        ResourceInterface::CURRENCY => 'currencyCode',
        //These are generated constants required
        //Do not remove until mapInputToExternal is better!
        'noAir' => 'noAir',
        'noCar' => 'noCar',
        'noRideshare'=> 'noRideshare',
        'noBikeshare' => 'noBikeshare',
        'oKind' => 'oKind',
        'dKind' => 'dKind',
    ];

    protected $origin_display_name = 'Origin';
    protected $destination_display_name = 'Destination';


    protected $externalToResultMapping = [
        'places' => 'places',
        'routes' => 'routes',
        'vehicles' => 'vehicles',
        'languageCode' => 'language_code',
        'currencyCode' => 'currency_code',

    ];
    protected $resultTransformations = [
        'routes' => 'orderByQuickest'
    ];

    public function __construct()
    {
        $this->lang = in_array(substr(Lang::locale(), 0, 2), ['en','de','fr','nl']) ? substr(Lang::locale(), 0, 2): 'en';
        parent::__construct('Search');
    }

    protected function mergeOriginCoordinates($params, $key)
    {
        if(isset($params[ResourceInterface::ORIGIN_LATITUDE], $params[ResourceInterface::ORIGIN_LONGITUDE])){
            $params[$key] = $params[ResourceInterface::ORIGIN_LATITUDE] . ',' . $params[ResourceInterface::ORIGIN_LONGITUDE];
        }
        return $params;
    }

    protected function mergeDestinationCoordinates($params, $key)
    {
        if(isset($params[ResourceInterface::DESTINATION_LATITUDE], $params[ResourceInterface::DESTINATION_LONGITUDE])){
            $params[$key] = $params[ResourceInterface::DESTINATION_LATITUDE] . ',' . $params[ResourceInterface::DESTINATION_LONGITUDE];
        }
        return $params;
    }

    protected function generateLanguageCode($params, $key)
    {
        $params[$key] = isset($params[ResourceInterface::LANGUAGE]) ? $params[ResourceInterface::LANGUAGE] : self::DEFAULT_LANGUAGE;
        return $params;
    }

    protected function generateCurrencyCode($params, $key)
    {
        $params[$key] = isset($params[ResourceInterface::CURRENCY]) ? $params[ResourceInterface::CURRENCY] : self::DEFAULT_CURRENCY;
        return $params;
    }

    protected function generateConstants($params, $key){

        $params['noAir'] = !self::ALLOW_AIR;
        $params['noCar'] = !self::ALLOW_CAR;
        $params['noRideshare'] = !self::ALLOW_RIDESHARE;
        $params['noBikeshare'] = 1;
        $params['oKind'] = 'street_address';
        $params['dKind'] = 'street_address';

        return $params;
    }

    public function setParams(array $params)
    {
        //Set Order by param
        $this->orderBy = isset($params['_order'])? $params['_order'] : self::DEFAULT_ORDER_BY_PREDICATE;

        if(isset($params[ResourceInterface::ORIGIN_ADDRESS_FOR_DISPLAY])){
            $this->origin_display_name = $params[ResourceInterface::ORIGIN_ADDRESS_FOR_DISPLAY];
        }
        if(isset($params[ResourceInterface::DESTINATION_ADDRESS_FOR_DISPLAY])){
            $this->destination_display_name = $params[ResourceInterface::DESTINATION_ADDRESS_FOR_DISPLAY];
        }
        parent::setParams($params);
    }

    public function getResult()
    {
        $result = new \ArrayObject(parent::getResult());

        //Get vehicle translations
        $vehicleTranslations = Config::get('resource_rome2rio_translations.' . $this->lang . '.vehicles');
        $timeTranslations = Config::get('resource_rome2rio_translations.' . $this->lang . '.time_units');
        $vehicleReplacementTranslations = Config::get('resource_rome2rio_translations.' . $this->lang . '.replace_vehicles_with');
        //The Rome2Rio results refer to another item in the result denoting a place
        //For more usable results the actual place names are placed there.
        foreach ($result['routes'] as &$resultItem)
        {
            if(isset($resultItem['arrPlace'], $resultItem['arrPlace'])){
                //Replace Origin and Destination with the display addresses provided in the input
                $depPlace = $result['places'][$resultItem['depPlace']]['shortName'] == 'Origin' ? $this->origin_display_name : $result['places'][$resultItem['depPlace']]['shortName'] ;
                $arrPlace = $result['places'][$resultItem['arrPlace']]['shortName'] == 'Destination' ? $this->destination_display_name : $result['places'][$resultItem['arrPlace']]['shortName'] ;

                $resultItem['depPlace'] = $depPlace;
                $resultItem['arrPlace'] = $arrPlace;
            }

            if(isset($resultItem['indicativePrices'], $resultItem['indicativePrices'][0], $resultItem['indicativePrices'][0]['price'])){
                $resultItem[ResourceInterface::PRICE_DEFAULT] = $resultItem['indicativePrices'][0]['price'];
            }

            if(isset($resultItem['totalDuration'])){
                $resultItem['totalDuration'] = $this->convertToHumanReadableDate($resultItem['totalDuration'], $timeTranslations);
            }

            if(isset($resultItem['name'])){
                $resultItem['name'] = str_replace(array_keys($vehicleReplacementTranslations), array_values($vehicleReplacementTranslations), $resultItem['name']);
            }

            foreach ($resultItem['segments'] as &$segment){

                //Replace Origin and Destination with the display addresses provided in the input for the segments
                $depPlace = $result['places'][$segment['depPlace']]['shortName'] == 'Origin' ? $this->origin_display_name : $result['places'][$segment['depPlace']]['shortName'] ;
                $arrPlace = $result['places'][$segment['arrPlace']]['shortName'] == 'Destination' ? $this->destination_display_name : $result['places'][$segment['arrPlace']]['shortName'] ;
                $segment['depPlace'] = $depPlace;
                $segment['arrPlace'] = $arrPlace;
                $segment['vehicleIcon'] = mb_strtolower($result['vehicles'][$segment['vehicle']]['name']);
                if(isset($segment['transitDuration'])){
                    $segment['transitDuration'] = $this->convertToHumanReadableDate($segment['transitDuration'], $timeTranslations);
                }

                //Translate the label if a translation exists
                $vehicleIndex = $result['vehicles'][$segment['vehicle']]['name'];
                $vehicleLabel = isset($vehicleTranslations[$vehicleIndex])
                    ? $vehicleTranslations[$vehicleIndex]
                    : $vehicleIndex;

                $segment['vehicle'] = $vehicleLabel;

                //Get indicative prices
                if(isset($segment['indicativePrices'], $segment['indicativePrices'][0], $segment['indicativePrices'][0]['price'])){
                    $segment[ResourceInterface::PRICE_DEFAULT] = $segment['indicativePrices'][0]['price'];
                }
            }
        }
        return $result['routes'];
    }

    protected function orderByQuickest($routes)
    {
        usort($routes, function ($a, $b) {
            return $a[$this->orderBy] < $b[$this->orderBy] ? -1 : 1;
        });
        return $routes;
    }

    protected function convertToHumanReadableDate($minutes, $timeTranslations)
    {
        $days = floor ($minutes / 1440);
        $processedData['days']['value'] = $days;
        $hours = floor (($minutes - $days * 1440) / 60);
        $processedData['hours']['value'] = $hours;
        $remainingMinutes = $minutes - ($days * 1440) - ($hours * 60);
        $processedData['minutes']['value'] = $remainingMinutes;

        $dayQuery = $days > 1 ? 'days' : 'day';
        $hourQuery = $hours > 1 ? 'hours' : 'hour';
        $minuteQuery = $remainingMinutes > 1 ? 'minutes' : 'minute';
        $processedData['days']['string'] = isset($timeTranslations[$dayQuery]) ? $timeTranslations[$dayQuery] : 'days';
        $processedData['hours']['string'] = isset($timeTranslations[$hourQuery]) ? $timeTranslations[$hourQuery] : 'hours';
        $processedData['minutes']['string'] = isset($timeTranslations[$minuteQuery]) ? $timeTranslations[$minuteQuery] : 'minutes';

        $outputString = '';
        foreach ($processedData as $timePeriod => $timeData){
            if($timeData['value'] == 0){
                continue;
            }
            $addition = empty($outputString)? '' : ' ';
            $outputString = $outputString. $addition . $timeData['value'] . ' ' . $timeData['string'];
        }

        return $outputString;
    }


}