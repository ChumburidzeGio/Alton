<?php

namespace App\Resources\Parkingci;

use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Exception\ParseException;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;

class ParkingciAbstractRequest extends MappedHttpMethodRequest
{
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i';
    const TIME_FORMAT = 'H:i';

    protected $authMethod = self::AUTH_HTTP_BASIC;

    public $resource2Request = true;

    /** @var string|null Name of key in the response result that contains the list of result items. */
    protected $resultKeyname = null;

    /**
     * ParkingciAbstractRequest constructor.
     *
     * @param string $methodPath
     * @param $httpMethod
     */
    public function __construct($methodPath = '', $httpMethod = self::METHOD_GET)
    {
        parent::__construct(((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    protected function isTestEnvironment()
    {
        return (bool)((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.testing'));
    }

    protected function getHttpClient()
    {
        $client = parent::getHttpClient();
        $client->setDefaultOption('allow_redirects', false);

        return $client;
    }

    protected function applyAuthentication(array $httpOptions)
    {
        $this->authData['username'] = ((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.username'));
        $this->authData['password'] = ((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.password'));

        return parent::applyAuthentication($httpOptions);
    }

    protected function applyParams(array $httpOptions)
    {
        $httpOptions['query']['json'] = '1';
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
                $this->setPrettyErrorString(ucfirst($errorData['error']));
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

    public function formatDate($inputDateTime)
    {
        // We assume the -local- timezone if none is present in $inputDate
        $dateTime = new \DateTime($inputDateTime);

        return $dateTime->format(self::DATE_FORMAT);
    }

    public function formatDateTime($inputDateTime)
    {
        // We assume the -local- timezone if none is present in $inputDate
        $dateTime = new \DateTime($inputDateTime);

        return $dateTime->format(self::DATETIME_FORMAT);
    }

    public function formatTime($inputDateTime)
    {
        // We assume the -local- timezone if none is present in $inputDate
        $dateTime = new \DateTime($inputDateTime);

        return $dateTime->format(self::TIME_FORMAT);
    }
}