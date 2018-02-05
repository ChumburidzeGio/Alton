<?php

namespace App\Resources\Multisafepay;

use App\Interfaces\ResourceInterface;
use App\Resources\MappedHttpMethodRequest;
use GuzzleHttp\Message\ResponseInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;


abstract class AbstractMultiSafepayRequest extends MappedHttpMethodRequest
{
    protected $httpBodyEncoding = self::DATA_ENCODING_JSON;
    protected $httpResultEncoding = self::DATA_ENCODING_JSON;

    public $resource2Request = true;
    protected $cacheDays = false;

    // List retrieved from (2016-10-06):
    // https://www.multisafepay.com/documentation/doc/API-Reference/#API-Reference-Createadirectorder
    protected $directPaymentMethods = ['IDEAL', 'BANKTRANS', 'DIRDEB', 'PAYAFTER', 'PAYPAL'];
    protected $issuerPaymentMethods = ['IDEAL'];

    protected $methodPath;

    protected $defaultSettings;
    protected $settings;

    public function __construct($methodPath = '', $httpMethod = self::METHOD_GET)
    {
        $this->methodPath = $methodPath;
        $this->httpMethod = $httpMethod;
        $this->defaultSettings = ((app()->configure('resource_multisafepay')) ? '' : config('resource_multisafepay.settings'));

        parent::__construct($this->defaultSettings['url'] . $this->methodPath);
    }

    public function setParams(array $params)
    {
        $this->settings = array_merge($this->defaultSettings, $this->getMultisafepaySettings($params));

        if (empty($this->settings['api_key']))
            $this->setErrorString('Multisafepay `api_key` not configured or passed.');

        if ($this->settings['test_environment'])
            $this->setUrl($this->settings['test_url'] . $this->methodPath);
        else
            $this->setUrl($this->settings['url'] . $this->methodPath);

        return parent::setParams(array_merge([
            ResourceInterface::LANGUAGE => $this->settings['language'],
            ResourceInterface::API_KEY => $this->settings['api_key'],
            ResourceInterface::TEST_ENVIRONMENT => $this->settings['test_environment'],
        ], $params));
    }

    protected function applyAuthentication(array $httpOptions)
    {
        $httpOptions['headers']['api_key'] = $this->settings['api_key'];

        return parent::applyAuthentication($httpOptions);
    }

    protected function applyParams(array $httpOptions)
    {
        // Always put locale input in query (even for POST)
        $httpOptions['query']['locale'] = $this->settings['language'];

        return parent::applyParams($httpOptions);
    }

    protected function parseResponse(ResponseInterface $response, $ignoreException = false)
    {
        $data = parent::parseResponse($response, $ignoreException);

        if (is_array($data) && (!isset($data['success']) || !$data['success'])) {

            if (!$ignoreException)
                $this->handleError($response, null);

            return $data;
        }

        if (is_array($data) && isset($data['data']))
            return $data['data'];

        return $data;
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        $errorData = $response ? $this->parseErrorResponse($response) : null;

        if (isset($errorData['error_info'], $errorData['error_code']))
            $this->setErrorString('MultiSafepay error ('. $errorData['error_code'] .'): `'. $errorData['error_info'] .'`');
        else
            return parent::handleError($response, $exception);
    }

    protected function convertToCents($value)
    {
        return ceil((float)$value * 100);
    }

    protected function getMultisafepaySettings(array $params)
    {
        $settings = [];

        if (isset($params[ResourceInterface::USER]) && isset($params[ResourceInterface::WEBSITE])) {

            $testEnv = $this->getRightSetting('multisafepay_test_environment', $params[ResourceInterface::USER], $params[ResourceInterface::WEBSITE]);
            $apiKey = $this->getRightSetting('multisafepay_api_key', $params[ResourceInterface::USER], $params[ResourceInterface::WEBSITE]);

            if ($testEnv !== null)
                $settings['test_environment'] = (bool)$testEnv;
            if ($apiKey !== null)
                $settings['api_key'] = $apiKey;
        }
        else if (!empty($params[ResourceInterface::API_KEY]))
        {
            $settings['api_key'] = $params[ResourceInterface::API_KEY];

            if (isset($params['test_environment']))
                $settings['test_environment'] = (bool)$params['test_environment'];
        }
        else
        {
            $this->setErrorString('Parameter `api_key`, or parameters `user` / `website` are required.');
        }

        if (isset($params[ResourceInterface::LANGUAGE]))
            $settings['language'] = $params[ResourceInterface::LANGUAGE];

        return $settings;
    }

    protected function getRightSetting($name, $user, $website)
    {
        $right = null;
        if (!empty($website))
            $right = DB::table('rights')->where('user_id', $user)->where('website_id', $website)->where('active', 1)->where('key', $name)->first();
        if (!$right) {
            $right = DB::table('rights')->where('user_id', $user)->where('website_id', 0)->where('active', 1)->where('key', $name)->first();
        }
        if (!$right)
            return null;

        return $right->value;
    }

    public function convertLanguageToLocale($value, $input, $fieldName)
    {
        if (empty($value))
            return null;

        // Already a locale?
        if (preg_match('~^[a-z]{2}\_[A-Z]{2}$~', $value)) {
            return $value;
        }

        // Do some default conversions
        $conversions = [
            'en' => 'en_US',
        ];
        if (isset($conversions[$value]))
            return $conversions[$value];

        // Try any
        if (strlen($value) == 2) {
            return $value .'_'. strtoupper($value);
        }

        $this->addErrorMessage($fieldName, 'invalid-language', 'This is an invalid language or locale.');
    }
}