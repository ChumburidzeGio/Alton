<?php
namespace App\Resources\Parkandfly;

use App\Interfaces\ResourceInterface;
use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;


class ParkandflyAbstractRequest extends MappedHttpMethodRequest
{
    // Geo Coordinate to use when we do not know one. Distance values will be incorrect.
    // For now this is the Schiphol entryhalls - but anything in NL will do.
    const DEFAULT_SEARCH_LATLON = ['lat' => 52.3089525, 'lng' => 4.7638375];
    const LANGUAGECODE_DUTCH = 'nl';
    const LANGUAGECODE_ENGLISH = 'en';
    const LANGUAGECODE_GERMAN = 'de';
    const DATETIME_FORMAT = 'Y-m-d H:i';
    const RESULT_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public $resource2Request = true;

    protected $receivedEmptyError = false;

    public function __construct($methodPath = '', $httpMethod = self::METHOD_GET)
    {
        parent::__construct(((app()->configure('resource_parkandfly')) ? '' : config('resource_parkandfly.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    public function applyAuthentication(array $httpOptions)
    {
        $httpOptions['query']['key'] = ((app()->configure('resource_parkandfly')) ? '' : config('resource_parkandfly.settings.apikey'));

        return parent::applyAuthentication($httpOptions);
    }

    public function executeFunction()
    {
        parent::executeFunction();

        // This web API sometimes returns errors with HTTP status code 200
        if (is_array($this->result) && isset($this->result['error'])) {
            $this->setErrorString('Server reports error: `' . $this->result['error'] . '`');
            $this->result = null;
        }
    }

    public function applyInputTransforms(Array $params)
    {
        if (isset($params[Parking::ORDER_ID]))
            $params = array_merge($params, $this->splitOrderId($params[Parking::ORDER_ID]));

        return parent::applyInputTransforms($params);
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        $rawBody = $response ? (string)$response->getBody() : '-none-';

        $errorData = $response ? $this->parseErrorResponse($response) : null;

        $errorMessage = $exception ? $exception->getMessage() : 'Unknown error';

        if (isset($errorData['error']))
        {
            $errorMessage = 'Server reports error: `'. json_encode($errorData['error']).'`';
        }
        else if ($errorData === [])
        {
            // This web API is a bit non-communicative on some errors :/
            $errorMessage = 'Server reports an error. No error message given.';
            $this->receivedEmptyError = true;
        }

        $this->setErrorString($errorMessage . (isset($errorData['error']['code']) ? ' ('. $errorData['error']['code'] .')' : '') . ' Body: `' . $rawBody .'`');
    }

    protected function formatDateTime($inputDateTime, $params, $key)
    {
        return $this->formatInputDateTime($inputDateTime, $params, $key, self::DATETIME_FORMAT);
    }

    protected function formatResultDateTime($inputDateTime)
    {
        // We assume the -local- timezone if none is present in $inputDate
        $dateTime = new \DateTime($inputDateTime);

        return $dateTime->format(self::RESULT_DATETIME_FORMAT);
    }

    protected function splitOrderId($orderId)
    {
        $info = explode('|', $orderId);

        return [
            'user_id' => isset($info[0]) ? $info[0] : '',
            'user_hash' => isset($info[1]) ? $info[1] : '',
            ResourceInterface::ORDER_ID => isset($info[2]) ? $info[2] : '',
        ];
    }

    protected function prefixOrderId($value)
    {
        if (isset($this->result['user']['id'], $this->result['user']['hash']))
            return $this->result['user']['id'] .'|'. $this->result['user']['hash'] .'|'. $value;
        else if (isset($this->inputParams[ResourceInterface::ORDER_ID]))
            return $this->inputParams[ResourceInterface::ORDER_ID];
        else
            return $value;
    }

    public function clearErrors()
    {
        parent::clearErrors();
        $this->receivedEmptyError = false;
    }
}