<?php

namespace App\Resources\Blaudirekt;

use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;


class BlaudirektAbstractRequest extends MappedHttpMethodRequest
{
    protected $httpMethod = self::METHOD_GET;

    public $resource2Request = true;
    protected $dataResult = true;

    protected $inputToExternalMapping = [];

    protected $cacheDays = 0;

    protected $defaultParams = [];

    protected $brokerReq = false;

    public function __construct($methodPath = '')
    {
        parent::__construct($this->brokerReq ? (Config::get('resource_blaudirekt.settings.url_broker') . $methodPath) : (Config::get('resource_blaudirekt.settings.url') . $methodPath));
    }

    public function applyAuthentication(array $httpOptions)
    {
        if ($this->brokerReq){
            $httpOptions = $this->getKundeAuth($httpOptions);
        } else{
            $httpOptions['debug']                    = false;
            $httpOptions['headers']['Authorization'] = Config::get('resource_blaudirekt.settings.bearer');
            $httpOptions['headers']['Content-Type']  = 'application/json';
        }
        return parent::applyAuthentication($httpOptions);
    }

    public function executeFunction()
    {
        parent::executeFunction();

        if($this->dataResult){
            $this->result = array_get($this->result, 'data', $this->result);
        }
    }


    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        if( ! $response && $exception){
            $this->setErrorString('Connection error: ' . $exception->getMessage());
            return;
        }//Handle 404 errors as they are not formatted as JSON;
        elseif($response->getStatusCode() === 404){
            $this->setErrorString(sprintf('Request URI: `%s` | Status: `%s %s`', $response->getEffectiveUrl(), $response->getStatusCode(), $response->getReasonPhrase()));

            return;
        }//Any other errors;
        else if($response && $response->json()){
            $errorData = $response->json();
            if(is_array($errorData) && isset($errorData['error'], $errorData['message'])){
                $this->setErrorString('Service reports: `' . $errorData['message'] . '`');
                return;
            }else if($exception){
                $this->setErrorString('Service connection error: `' . $exception->getMessage() . '`');
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
            'family'       => 'familie',
            'singleparent' => 'alleinerziehende',
        ], $value, $value);
    }

    public function setParams(array $params)
    {
        parent::setParams($params);

        $this->params = array_merge($this->params, $this->defaultParams);
    }

    /**
     * @param array $httpOptions
     *
     * @return array
     */
    protected function getKundeAuth(array $httpOptions)
    {
        $timestamp = date("Y-m-d\TH:i:s", strtotime("+10 minutes"));
        $nonce     = sha1(str_random());
        $apiKey    = Config::get('resource_blaudirekt.settings.api_key');
        $apiId     = Config::get('resource_blaudirekt.settings.api_id');
        $digest    = sha1($nonce . $timestamp . $apiKey);

        $httpOptions['headers']['X-Dio-Timestamp'] = $timestamp;
        $httpOptions['headers']['X-Dio-Api-Id']    = $apiId;
        $httpOptions['headers']['X-Dio-Nonce']     = $nonce;
        $httpOptions['headers']['X-Dio-Digest']    = $digest;
        return $httpOptions;
    }
}