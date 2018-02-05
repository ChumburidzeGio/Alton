<?php
namespace App\Resources\Inrix\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\Paston\InrixAbstractRequest;
use Illuminate\Support\Facades\App;

/**
 * Class ParkingLots
 *
 * Get parking lots for a location and radius.
 * (it has more geo-filtering options & input parameters, but we do not use them)
 *
 * See: <http://docs.parkme.com/parking/lots/>
 *
 * @package App\Resources\Inrix\Methods
 */
class ParkingLots extends InrixAbstractRequest
{
    protected $inputTransformations = [
        ResourceInterface::ARRIVAL_DATE => 'formatDateTime',
        ResourceInterface::DEPARTURE_DATE => 'formatDateTime',
        ResourceInterface::RADIUS => 'convertKmToMeter',
    ];
    protected $inputGenerators = [
        'calculateDurationFromDeparture',
    ];
    protected $inputToExternalMapping = [
        ResourceInterface::LATITUDE => 'point.0',
        ResourceInterface::LONGITUDE => 'point.1',
        ResourceInterface::LIMIT => 'limit',
        ResourceInterface::OFFSET => 'offset',
        ResourceInterface::RADIUS => 'radius',
        ResourceInterface::ARRIVAL_DATE => 'entry_time',
        ResourceInterface::DURATION => 'duration',
        ResourceInterface::LOCALE => 'locale',
    ];
    protected $externalToResultMapping = [
        'id' => ResourceInterface::PARKING_ID,
        'calculatedRates.0.rateCost' => ResourceInterface::PRICE_ACTUAL,
        'isOpen' => ResourceInterface::IS_UNAVAILABLE,
        'distance' => ResourceInterface::DISTANCE_TO_DESTINATION,
        'navigationAddress' => ResourceInterface::ADDRESS,
        'name' => ResourceInterface::NAME,
        'operator' => ResourceInterface::COMPANY_NAME,
        'spacesTotal' => ResourceInterface::PARKING_SPACES_TOTAL,
        'point.coordinates.0' => ResourceInterface::LOCATION_LONGITUDE,
        'point.coordinates.1' => ResourceInterface::LOCATION_LATITUDE,
    ];
    protected $resultTransformations = [
        ResourceInterface::IS_UNAVAILABLE => 'invertBoolean',
        ResourceInterface::ADDRESS => 'implodeAddress',
        ResourceInterface::NAME => 'addCompanyName',
        ResourceInterface::DISTANCE_TO_DESTINATION => 'convertMeterToKm',
        ResourceInterface::TIME_TO_DESTINATION => 'guessTimeToDestination',
        ResourceInterface::DESCRIPTION => 'createHtmlDescription',
//        ResourceInterface::AVAILABLE_OPTIONS => 'extractAvailableOptions', // Unsupported for now, because price does not change
    ];

    protected $amenityIdToOptionId = [
        4 => 12, // EV Chargers
        9 => 4, // Covered Parking Available
        5 => 1, // Car Wash
    ];

    protected $languageToLocale = [
        'nl' => 'nl-NL',
        'fr' => 'fr-FR',
        'de' => 'de-DE',
        'en' => 'en-US',
    ];

    public function __construct()
    {
        parent::__construct('parking', 'lots');
    }

    protected function mapInputToExternal(array $inputParams, array $params, $unsetNullValues = true, $unsetEmptyArrays = true)
    {
        if (!isset($params[ResourceInterface::LOCALE])) {
            $params[ResourceInterface::LOCALE] = array_get($this->languageToLocale, substr(App::getLocale(), 0, 2));
        }

        $params = parent::mapInputToExternal($inputParams, $params, $unsetNullValues, $unsetEmptyArrays);

        if (isset($params['point']) && is_array($params['point']))
            $params['point'] = implode('|', $params['point']);

        if (!isset($params['limit']))
            $params['limit'] = 10;

        return $params;
    }

    protected function calculateDurationFromDeparture(array $params, $key)
    {
        if (isset($params[ResourceInterface::ARRIVAL_DATE], $params[ResourceInterface::DEPARTURE_DATE]))
            $params[ResourceInterface::DURATION] = floor((strtotime($params[ResourceInterface::DEPARTURE_DATE]) - strtotime($params[ResourceInterface::ARRIVAL_DATE]))/60);

        return $params;
    }

    protected function invertBoolean($value)
    {
        return !$value;
    }

    protected function implodeAddress($value)
    {
        return array_get($value, 'street') .", ". array_get($value, 'city');
    }

    protected function extractAvailableOptions($value, $item)
    {
        $options = [];

        foreach ($item['@unmapped']['amenities'] as $amenity) {
            if (isset($this->amenityIdToOptionId[$amenity['id']]) && $amenity['value'])
                $options[] = $this->amenityIdToOptionId[$amenity['id']];
        }

        return $options;
    }

    protected function addCompanyName($value, $item)
    {
        return ($item[ResourceInterface::COMPANY_NAME] ? $item[ResourceInterface::COMPANY_NAME] .' ' : ''). $value;
    }

    public function getResult()
    {
        $result = parent::getResult();

        foreach ($result as $nr => $item) {
            $result[$nr][ResourceInterface::RESOURCE_KEY] = 'closest-'. ($nr+1);
        }

        usort($result, function ($a, $b) {
            return $b[ResourceInterface::PRICE_ACTUAL] - $a[ResourceInterface::PRICE_ACTUAL];
        });

        foreach ($result as $nr => $item) {
            if ($nr > 1)
                break;
            $result[$nr][ResourceInterface::RESOURCE_KEY] = 'cheapest-'. ($nr+1);
        }

        return $result;
    }

    public function formatDateTime($value, $key)
    {
        return $this->formatInputDateTime($value, [], $key, 'Y-m-d\TH:i');
    }

    public function convertKmToMeter($value)
    {
        return $value * 1000;
    }

    public function convertMeterToKm($value)
    {
        return $value / 1000;
    }

    public function guessTimeToDestination($value, $item)
    {
        // We walk 5 km per hour, and we assume the walking distance is max 1.5 times the as-the-crow-flies distance
        // Return in minutes
        return floor((($item[ResourceInterface::DISTANCE_TO_DESTINATION] / 5000) * 1.5) * 60);
    }

    public function createHtmlDescription($value, $item)
    {
        $description = '';

        if (!empty($item[ResourceInterface::ADDRESS]))
            $description .= '<p>'. $this->implodeAddress($item[ResourceInterface::ADDRESS]) .'</p>';
        if (!empty($item['@unmapped']['note']))
            $description .= '<p>'. $item['@unmapped']['note'] .'</p>';
        if (!empty($item['@unmapped']['rateCard']))
            $description .= '<ul>'. implode('', array_map(function ($i) { return '<li>'. $i .'</li>';}, $item['@unmapped']['rateCard'])) .'</ul>';

        return $description;
    }
}