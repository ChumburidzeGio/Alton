<?php
namespace App\Resources\Blaudirekt;

use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;


class BlaudirektAbstractRequest extends MappedHttpMethodRequest
{
    protected $httpMethod = self::METHOD_GET;

    public $resource2Request = true;

    protected $inputToExternalMapping = [];

    protected $cacheDays = 0;

    protected $defaultParams = [];

    public function __construct($methodPath = '')
    {
        parent::__construct(((app()->configure('resource_blaudirekt')) ? '' : config('resource_blaudirekt.settings.url')) . $methodPath);
    }

    public function applyAuthentication(array $httpOptions)
    {
        $httpOptions['debug'] = false;
        $httpOptions['headers']['Authorization'] = ((app()->configure('resource_blaudirekt')) ? '' : config('resource_blaudirekt.settings.bearer'));
        $httpOptions['headers']['Content-Type'] = 'application/json';

        return parent::applyAuthentication($httpOptions);
    }

    public function executeFunction()
    {
        parent::executeFunction();

        $this->result = array_get($this->result, 'data', $this->result);
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        if (!$response && $exception)
        {
            $this->setErrorString('Connection error: '. $exception->getMessage());
            return;
        }
        else if ($response && $response->json())
        {
            $errorData = $response->json();
            if (is_array($errorData) && isset($errorData['error'], $errorData['message']))
            {
                $this->setErrorString('Service reports: `'. $errorData['message'] .'`');
                return;
            }
            else if ($exception)
            {
                $this->setErrorString('Service connection error: `'. $exception->getMessage() .'`');
                return;
            }
        }

        $this->setErrorString('Unknown error.');
    }

    protected function convertDate($value)
    {
        return strtotime($value);
    }

    protected function convertRange($value)
    {
        return array_get([
            'family' => 'familie',
            'singleparent' => 'alleinerziehende',
        ], $value, $value);
    }

    public function setParams(array $params)
    {
        parent::setParams($params);

        $this->params = array_merge($this->params, $this->defaultParams);
    }
}