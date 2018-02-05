<?php
namespace App\Resources\Knip;


use App\Resources\MappedHttpMethodRequest;
use Illuminate\Support\Facades\Config;

class AbstractKnipRequest extends MappedHttpMethodRequest
{
    //resource2 request!!
    public $resource2Request = true;

    protected $cacheDays = false;

    protected $httpBodyEncoding = self::DATA_ENCODING_JSON;

    public function __construct($methodPath = '', $httpMethod = self::METHOD_GET)
    {
        parent::__construct(((app()->configure('resource_knip')) ? '' : config('resource_knip.settings.url')) . $methodPath);
        $this->httpMethod = $httpMethod;
    }

    public function applyAuthentication(array $httpOptions)
    {
        $httpOptions['headers']['Authorization'] = ((app()->configure('resource_knip')) ? '' : config('resource_knip.settings.apikey'));
        $httpOptions['headers']['X-Country'] = 'de';
        return parent::applyAuthentication($httpOptions);
    }
}