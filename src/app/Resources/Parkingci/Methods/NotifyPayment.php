<?php

namespace App\Resources\Parkingci\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\HttpMethodRequest;
use Illuminate\Support\Facades\Config;

class NotifyPayment extends HttpMethodRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;
    protected $httpResultEncoding = self::DATA_ENCODING_TEXT;

    public function __construct()
    {
        parent::__construct(((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.notify_url')));
    }

    public function applyAuthentication(array $httpOptions)
    {
        $httpOptions['query']['access_key'] = ((app()->configure('resource_parkingci')) ? '' : config('resource_parkingci.settings.notify_access_key'));
        return $httpOptions;
    }

    public function setParams(array $params)
    {
        if (empty($params[ResourceInterface::ORDER_ID])) {
            $this->setErrorString('Order ID is required.');
            return;
        }

        $params = [
            'transactionid' => $params[ResourceInterface::ORDER_ID],
            'status' => array_get($params, ResourceInterface::STATUS),
            'transaction_costs' => array_get($params, ResourceInterface::TRANSACTION_COSTS),
        ];

        parent::setParams($params);
    }

    public function getResult()
    {
        // If no HTTP error, success?
        return [
            ResourceInterface::SUCCESS => true,
        ];
    }
}