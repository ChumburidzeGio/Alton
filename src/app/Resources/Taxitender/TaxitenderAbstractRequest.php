<?php


namespace App\Resources\Taxitender;

use App\Interfaces\ResourceInterface;
use App\Resources\MappedHttpMethodRequest;
use Cache;
use Config;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

class TaxitenderAbstractRequest extends MappedHttpMethodRequest
{
    const DATETIME_FORMAT_UTC = 'Y-m-d\TH:i:s\Z';

    protected $cacheDays = false;
    protected $httpBodyEncoding = self::DATA_ENCODING_URLENCODED;
    protected $authMethod = self::AUTH_CUSTOM;
    public $resource2Request = true;
    protected $resultKeyname = 'response';

    protected $supportedLanguages = ['NL', 'EN', 'FR', 'DE'];

    public function __construct($methodPath = '', $httpMethod = self::METHOD_POST)
    {
        parent::__construct(((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    protected function applyAuthentication(array $httpOptions)
    {
        $httpOptions['body'] = array_merge([
            'apiLogin'    => ((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.auth.apiLogin')),
            'apiToken'    => ((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.auth.apiToken')),
            'label'       => ((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.auth.label')),
            'affiliateID' => ((app()->configure('resource_taxitender')) ? '' : config('resource_taxitender.settings.auth.affiliateID')),
        ], array_get($httpOptions, 'body', []));

        return parent::applyAuthentication($httpOptions);
    }

    protected function parseResponse(ResponseInterface $response, $ignoreException = false)
    {
        $data = parent::parseResponse($response, $ignoreException);

        if (array_get($data, 'responseCode') !== '1000_SUCCESS') {

            if (!$ignoreException) {
                $this->handleError($response, null);
            }

            return $data;
        }
        if (is_array($data) && isset($data['status'], $data['message']) && $data['status'] == 'failed') {

            if (!$ignoreException) {
                $this->handleError($response, null);
            }

            return $data;
        }

        return $data;
    }

    protected function handleError(ResponseInterface $response = null, \Exception $exception = null)
    {
        $errorData = $response ? $this->parseErrorResponse($response) : null;

        if (is_array($errorData) && isset($errorData['responseCode'])) {
            $this->setErrorString('Service reported error: `'. $errorData['responseCode'] .'`.');
            return;
        }

        parent::handleError($response, $exception);
    }

    public function getResult()
    {
        if ($this->resultKeyname) {
            if (is_array($this->result) && !isset($this->result[$this->resultKeyname])) {
                $this->setErrorString('Unexpected result, result item `' . $this->resultKeyname . '` not found.');
            } else {
                $this->result = $this->result[$this->resultKeyname];
            }
        }

        return parent::getResult();
    }

    public static function mergeRides($toRide, $fromRide)
    {
        return [
            ResourceInterface::SEARCH_QUERY_ID          => $toRide[ ResourceInterface::SEARCH_QUERY_ID] . '|' . $fromRide[ResourceInterface::SEARCH_QUERY_ID],
            ResourceInterface::SEARCH_QUERY_RESULT_ID   => $toRide[ResourceInterface::SEARCH_QUERY_RESULT_ID] . '|' . $fromRide[ResourceInterface::SEARCH_QUERY_RESULT_ID],
            ResourceInterface::PRICE_ACTUAL             => $toRide[ResourceInterface::PRICE_ACTUAL] + $fromRide[ResourceInterface::PRICE_ACTUAL],
            ResourceInterface::TITLE                    => implode('|', array_unique([$toRide[ResourceInterface::TITLE], $fromRide[ResourceInterface::TITLE]])),
            ResourceInterface::DESCRIPTION              => implode('|', array_unique([$toRide[ResourceInterface::DESCRIPTION], $fromRide[ResourceInterface::DESCRIPTION]])),
            ResourceInterface::IMAGE                    => implode('|', array_unique([$toRide[ResourceInterface::IMAGE], $fromRide[ResourceInterface::IMAGE]])),
            ResourceInterface::CATEGORY                 => implode('|', array_unique([$toRide[ResourceInterface::CATEGORY], $fromRide[ResourceInterface::CATEGORY]])),
            ResourceInterface::DISTANCE                 => implode('|', array_unique([$toRide[ResourceInterface::DISTANCE], $fromRide[ResourceInterface::DISTANCE]])),
            ResourceInterface::TIME                     => implode('|', array_unique([$toRide[ResourceInterface::TIME], $fromRide[ResourceInterface::TIME]])),
            ResourceInterface::BRAND_LOGO               => $fromRide[ResourceInterface::BRAND_LOGO],
            ResourceInterface::PASSENGERS_CAPACITY      => min($toRide[ResourceInterface::PASSENGERS_CAPACITY], $fromRide[ResourceInterface::PASSENGERS_CAPACITY]),
            '@unmapped' => ['to' => $toRide['@unmapped'], 'from' => $fromRide['@unmapped']],
        ];
    }

    /**
     * @param $__id
     *
     * @return array
     */
    public function getFromCache($__id)
    {
        return array_filter(array_map(function ($id) {
            return Cache::get($id);
        }, is_array($__id) ? $__id : explode(',', $__id)));
    }

    protected function getHttpClient()
    {
        //@TODO: move that to some parent class like HttpMethodRequest
        return new Client(['defaults' => ['connect_timeout' => 2]]);
    }

    protected function secondsToMinutes($seconds)
    {
        return ceil($seconds / 60);
    }

    protected function implode($val)
    {
        $val = call_user_func_array('array_merge', array_map(function ($p) {
            return explode(',', $p);
        }, (array) $val));

        return count($val) === 1 ? reset($val) : '';
    }

    protected function formatDateTimeUtc($inputDateTime, $params, $key)
    {
        $dateTime = new \DateTime($inputDateTime);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        return $this->formatInputDateTime($dateTime->format('c'), $params, $key, self::DATETIME_FORMAT_UTC);
    }
}