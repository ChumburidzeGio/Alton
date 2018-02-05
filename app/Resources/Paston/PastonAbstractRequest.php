<?php
namespace App\Resources\Paston;

use App\Resources\MappedHttpMethodRequest;
use Illuminate\Support\Facades\Config;


class PastonAbstractRequest extends MappedHttpMethodRequest
{
    public $resource2Request = true;
    protected $cacheDays = false;

    protected $httpBodyEncoding = self::DATA_ENCODING_JSON;
    protected $httpResultEncoding = self::DATA_ENCODING_JSON;

    public function __construct($methodPath = '', $httpMethod = self::METHOD_POST)
    {
        parent::__construct(((app()->configure('resource_paston')) ? '' : config('resource_paston.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    public function applyAuthentication(array $httpOptions)
    {
        $httpOptions['headers']['X-Auth-Token'] = ((app()->configure('resource_paston')) ? '' : config('resource_paston.settings.apikey'));

        return parent::applyAuthentication($httpOptions);
    }
}