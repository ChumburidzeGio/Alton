<?php
namespace App\Resources\Parkingpro;

use App\Interfaces\ResourceInterface;
use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;


class ParkingproAbstractRequest extends MappedHttpMethodRequest
{
    const DATETIME_FORMAT = \DateTime::ISO8601;

    protected $completeResult = null;

    public $resource2Request = true;

    protected $cacheDays = false;

    protected $parkingId = null;

    public function __construct($methodPath = '', $httpMethod = self::METHOD_GET)
    {
        parent::__construct(((app()->configure('resource_parkingpro')) ? '' : config('resource_parkingpro.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    public function applyInputTransforms(array $params)
    {
        $params = $this->extractParkingIds($params);

        return parent::applyInputTransforms($params);
    }

    public function applyAuthentication(array $httpOptions)
    {
        $httpOptions['headers']['X-Api-Key'] = ((app()->configure('resource_parkingpro')) ? '' : config('resource_parkingpro.settings.apikey'));

        return parent::applyAuthentication($httpOptions);
    }

    public function applyResultTransforms(array $result)
    {
        // Insert parkingId into any output IDs
        if (isset($result[0]))
        {
            foreach ($result as $key => $item)
                $result[$key] = $this->insertParkingIds($item);
        }
        else
        {
            $result = $this->insertParkingIds($result);
        }

        return parent::applyResultTransforms($result);
    }

    protected function extractParkingIds(array $params)
    {
        // Extract parkingId from other identifiers
        foreach ([ResourceInterface::LOCATION_ID, ResourceInterface::ORDER_ID] as $inputId)
            if (isset($params[$inputId]) && is_string($params[$inputId]) && str_contains($params[$inputId], '|'))
                $params = array_merge($params, $this->extractParkingId($params[$inputId], $inputId));

        if (isset($params[ResourceInterface::PARKING_ID]))
            $this->parkingId = $params[ResourceInterface::PARKING_ID];

        return $params;
    }

    protected function extractParkingId($id, $idName)
    {
        $info = explode('|', $id);

        return [
            ResourceInterface::PARKING_ID => isset($info[0]) ? $info[0] : '',
            $idName => isset($info[1]) ? $info[1] : '',
        ];
    }

    protected function insertParkingIds(array $array, $idNames = [ResourceInterface::LOCATION_ID, ResourceInterface::ORDER_ID])
    {
        foreach ($idNames as $idName)
            if (isset($array[$idName]) && !str_contains($array[$idName], '|') )
            {
                if (isset($array[ResourceInterface::PARKING_ID]))
                    $array[$idName] = $array[ResourceInterface::PARKING_ID] . '|' . $array[$idName];
                else if (isset($this->parkingId))
                    $array[$idName] = $this->parkingId . '|' . $array[$idName];
            }

        return $array;
    }

    protected function applyParams(array $httpOptions)
    {
        if (isset($this->params['parkingId']))
        {
            // Parking ID always needs to be in the url query params, even when POST/PUTing
            $httpOptions['query']['parkingId'] = $this->params['parkingId'];
            unset($this->params['parkingId']);
        }

        return parent::applyParams($httpOptions);
    }

    public function formatDateTime($inputDateTime)
    {
        // We assume the -local- timezone if none is present in $inputDate
        $dateTime = new \DateTime($inputDateTime);

        return $dateTime->format(self::DATETIME_FORMAT);
    }

    public function formatResultDateTime($dateTime)
    {
        $dateTime = new \DateTime($dateTime);

        return $dateTime->format('Y-m-d H:i:s');
    }

    public function formatOptions($options)
    {
        if ($options === '' || $options === null)
            return [];
        if (!is_array($options))
            return explode(',', (string)$options);

        return $options;
    }

    public function formatOptionsCommaSeparated($options)
    {
        return implode(',', $this->formatOptions($options));
    }

    protected function parseResponse(ResponseInterface $response, $ignoreException = false)
    {
        $result = parent::parseResponse($response, $ignoreException);

        if (isset($result['data']))
        {
            $this->completeResult = $result;
            return $result['data'];
        }

        return $result;
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        $rawBody = $response ? (string)$response->getBody() : null;

        $errorData = $response ? $this->parseErrorResponse($response) : null;

        if (!isset($errorData['error']['message']))
            return parent::handleError($response, $exception);

        // There may be more specific messages in $jsonError['error']['errors'], but these are not enduser-friendly or very useful for an API user.
        // (so we do not put them in addErrorMessage())

        $this->setErrorString($errorData['error']['message'] . ' (' . (isset($errorData['error']['code']) ? $errorData['error']['code'] : '?') . '): ' . $rawBody);
    }
}