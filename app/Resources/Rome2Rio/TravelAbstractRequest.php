<?php

namespace App\Resources\Rome2Rio\Travel;

use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Exception\ParseException;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;

class TravelAbstractRequest extends MappedHttpMethodRequest
{

    public $resource2Request = true;

    protected $isTestEnvironment = false;

    /** @var string|null Name of key in the response result that contains the list of result items. */
    protected $resultKeyname = null;

    /**
     * GoogleGeolocationAbstractRequest constructor.
     *
     * @param string $methodPath
     * @param $httpMethod
     */
    public function __construct($methodPath = '', $httpMethod = self::METHOD_GET)
    {
        parent::__construct(Config::get('resource_rome2rio.settings.url') . $methodPath);
//        $this->isTestEnvironment = (bool)((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.testing'));
        $this->httpMethod = $httpMethod;
    }

    protected function getHttpClient()
    {
        $client = parent::getHttpClient();
        $client->setDefaultOption('allow_redirects', false);

        return $client;
    }

    protected function applyAuthentication(array $httpOptions)
    {
        $httpOptions['query']['key'] = Config::get('resource_rome2rio.settings.key');
        return parent::applyAuthentication($httpOptions);
    }

    protected function applyParams(array $httpOptions)
    {
        //TODO: CHANGE THIS ACCORDING TO DOCUMENTATION
        return parent::applyParams($httpOptions);
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        if ($exception instanceof ParseException)
            $this->setErrorString($exception->getMessage() .'. Body: '. $response->getBody());
        else
        {
            $errorData = $response ? $this->parseErrorResponse($response) : null;

            if (isset($errorData['error'])) {
                $this->setErrorString('Error from service: `' . $errorData['error'] . '` - ' . json_encode($errorData));
                return;
            }
            if (isset($errorData['message'])) {
                $this->setErrorString('Error from service: `' . $errorData['message'] . '` - ' . json_encode($errorData));
                return;
            }
        }

        parent::handleError($response, $exception);
    }

    protected function parseResponse(ResponseInterface $response, $ignoreException = false)
    {
        $data = parent::parseResponse($response, $ignoreException);

        if (is_array($data) && isset($data['error'])) {

            if (!$ignoreException)
                $this->handleError($response, null);

            return $data;
        }
        if (is_array($data) && isset($data['status'], $data['message']) && $data['status'] == 'failed') {

            if (!$ignoreException)
                $this->handleError($response, null);

            return $data;
        }

        return $data;
    }

    public function getResult()
    {
        if ($this->resultKeyname)
        {
            if (is_array($this->result) && !isset($this->result[$this->resultKeyname]))
                $this->setErrorString('Unexpected result, result item `' . $this->resultKeyname . '` not found.');
            else
                $this->result = $this->result[$this->resultKeyname];
        }

        return parent::getResult();
    }
}