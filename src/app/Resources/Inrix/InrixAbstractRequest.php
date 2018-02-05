<?php

namespace App\Resources\Paston;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;


class InrixAbstractRequest extends MappedHttpMethodRequest
{
    public $resource2Request = true;
    protected $cacheDays = false;

    protected $useAccessToken = true;

    protected $httpBodyEncoding = self::DATA_ENCODING_URLENCODED;
    protected $httpResultEncoding = self::DATA_ENCODING_JSON;

    public function __construct($apiName, $methodPath = '', $httpMethod = self::METHOD_GET)
    {
        parent::__construct(((app()->configure('resource_inrix')) ? '' : config('resource_inrix.settings.'. $apiName .'_api_url')) . $methodPath);

        $this->httpMethod = $httpMethod;
    }

    public function applyAuthentication(array $httpOptions)
    {
        if ($this->useAccessToken)
            $httpOptions['query']['accesstoken'] = $this->getAccessToken();

        return parent::applyAuthentication($httpOptions);
    }

    protected function getAccessToken()
    {
        $tokenInfoHash = md5(((app()->configure('resource_inrix')) ? '' : config('resource_inrix.settings.app_id')) . ':'. ((app()->configure('resource_inrix')) ? '' : config('resource_inrix.settings.hash_token')));

        $tokenData = Cache::tags('inrix')->get('inrix-tokendata-'. $tokenInfoHash, null);

        // No token data, or token would expire in an hour or so
        if (!$tokenData || time() > $tokenData[ResourceInterface::EXPIRATION_DATE] - rand(1000, 3600)) {
            $tokenData = ResourceHelper::callResource2('auth_app_token.inrix', [
                ResourceInterface::APP_ID => ((app()->configure('resource_inrix')) ? '' : config('resource_inrix.settings.app_id')),
                ResourceInterface::HASH_TOKEN => ((app()->configure('resource_inrix')) ? '' : config('resource_inrix.settings.hash_token')),
            ]);

            $tokenData[ResourceInterface::EXPIRATION_DATE] = strtotime($tokenData[ResourceInterface::EXPIRATION_DATE]);
            Cache::tags('inrix')->add('inrix-tokendata-'. $tokenInfoHash, $tokenData, floor(($tokenData[ResourceInterface::EXPIRATION_DATE] - time()) / 60));
        }

        return $tokenData[ResourceInterface::TOKEN];
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        $errorData = $response ? $this->parseErrorResponse($response) : null;

        if (isset($errorData['error']['userMessage'])) {
            $this->setErrorString('Error from service: `'. $errorData['error']['userMessage'] .'` - '. json_encode($errorData['error']));
            return;
        }

        if (isset($errorData['Error']['ErrorDescription'])) {
            $this->setErrorString('Error from service: `'. $errorData['Error']['ErrorDescription'] .'` - '. json_encode($errorData['Error']));
            return;
        }


        parent::handleError($response, $exception);
    }

    public function getResult()
    {
        $result = $this->result;

        if (!isset($result['result'])) {
            $this->setErrorString('Cannot find result in response.');
            return;
        }

        $this->result = $result['result'];

        return parent::getResult();
    }
}