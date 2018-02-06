<?php


namespace App\Resources\Taxiboeken;

use App\Resources\MappedHttpMethodRequest;
use Config;
use GuzzleHttp\Message\ResponseInterface;

class TaxiboekenAbstractRequest extends MappedHttpMethodRequest
{
    const DATETIME_FORMAT_UTC = 'Y-m-d\TH:i:s\Z';

    protected $cacheDays = false;
    protected $authMethod = self::AUTH_CUSTOM;
    public $resource2Request = true;
    protected $httpBodyEncoding = self::DATA_ENCODING_JSON;

    public function __construct($methodPath = '', $httpMethod = self::METHOD_POST)
    {
        parent::__construct(((app()->configure('resource_taxiboeken')) ? '' : config('resource_taxiboeken.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    protected function applyAuthentication(array $httpOptions)
    {
        $httpOptions['headers']['Authorization'] = ((app()->configure('resource_taxiboeken')) ? '' : config('resource_taxiboeken.settings.api_key'));

        return parent::applyAuthentication($httpOptions);
    }

    protected function parseResponse(ResponseInterface $response, $ignoreException = false)
    {
        $data = parent::parseResponse($response, $ignoreException);

        if (isset($data['error']) && !$ignoreException) {
            $this->handleError($response, null);
            return $data;
        }
        else {
            return $data;
        }
    }

    protected function formatDateTime($inputDateTime, $params, $key)
    {
        return date(self::DATETIME_FORMAT_UTC, strtotime($inputDateTime));
    }

    protected function explode($input)
    {
        return call_user_func_array('array_merge', array_map(function ($p) {
            return array_map('trim', explode(',', $p));
        }, (array) $input));
    }

    protected function priceToDecimal($price)
    {
        return $price / 100;
    }
}