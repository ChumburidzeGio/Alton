<?php

namespace App\Resources\Allinone\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use App\Resources\BasicAuthRequest;
use App\Helpers\ResourceHelper;
use App\Listeners\Resources2\RestListener;


class Contract extends AbstractMethodRequest
{
    public $params           = [];
    public $result           = [];
    public $resource2Request = true;

    protected $cacheDays     = -1;

    public function setParams(Array $params)
    {
        $this->params[ResourceInterface::GENDER]            = $params[ResourceInterface::GENDER];
        $this->params[ResourceInterface::FIRST_NAME]        = $params[ResourceInterface::FIRST_NAME];
        $this->params[ResourceInterface::INSERTION]         = $params[ResourceInterface::INSERTION];
        $this->params[ResourceInterface::LAST_NAME]         = $params[ResourceInterface::LAST_NAME];
        $this->params[ResourceInterface::PHONE_PREFIX]      = $params[ResourceInterface::PHONE_PREFIX];
        $this->params[ResourceInterface::PHONE]             = $params[ResourceInterface::PHONE];
        $this->params[ResourceInterface::EMAIL]             = $params[ResourceInterface::EMAIL];
        $this->params[ResourceInterface::BANK_ACCOUNT_IBAN] = $params[ResourceInterface::BANK_ACCOUNT_IBAN];
    }

    function executeFunction()
    {
        $orderData = [
            ResourceInterface::GENDER               => array_get($this->params, ResourceInterface::GENDER),
            ResourceInterface::FIRST_NAME           => array_get($this->params, ResourceInterface::FIRST_NAME),
            ResourceInterface::INSERTION            => array_get($this->params, ResourceInterface::INSERTION),
            ResourceInterface::LAST_NAME            => array_get($this->params, ResourceInterface::LAST_NAME),
            ResourceInterface::PHONE_PREFIX         => array_get($this->params, ResourceInterface::PHONE_PREFIX),
            ResourceInterface::PHONE                => array_get($this->params, ResourceInterface::PHONE),
            ResourceInterface::EMAIL                => array_get($this->params, ResourceInterface::EMAIL),
            ResourceInterface::BANK_ACCOUNT_IBAN    => "it works",

        ];

        $order = ResourceHelper::callResource2('order.allinone', $orderData, RestListener::ACTION_STORE);

    }


    public function getResult()
    {
        return $this->result;
    }
}