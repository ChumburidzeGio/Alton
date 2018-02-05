<?php

namespace App\Resources\Multisafepay;

use App\Resources\AbstractMethodRequest;

class AbstractMultiSafepayMetaRequest extends AbstractMethodRequest
{
    public $resource2Request = true;

    protected $cacheDays = false;

    protected $params = [];
    protected $result = null;

    public function setParams(Array $params)
    {
        $this->params = array_merge($this->getDefaultParams(), $params);
    }

    public function getDefaultParams()
    {
        return [];
    }

    public function getResult()
    {
        return $this->result;
    }
}