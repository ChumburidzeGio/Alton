<?php

namespace App\Resources\Telecombinatie\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Config;

/**
 * User: Roeland Werring
 * Date: 08/07/15
 * Time: 11:29
 *
 */
class Payment extends AbstractMethodRequest
{
    protected $cacheDays = false;
    protected $params = [];
    protected $website = null;

    protected $arguments = [

        ResourceInterface::RETURN_URL_OK     => [
            'rules' => 'required',
        ],
        ResourceInterface::RETURN_URL_CANCEL => [
            'rules' => 'required',
        ],
        ResourceInterface::RETURN_URL_ERROR  => [
            'rules' => 'required',
        ],
        ResourceInterface::RETURN_URL_REJECT => [
            'rules' => 'required',
        ],
        ResourceInterface::BANK_ACCOUNT_BIC  => [
            'rules' => self::VALIDATION_REQUIRED_BANK,
        ],
        ResourceInterface::IP                => [
            'rules' => 'required',
        ],
        ResourceInterface::WEBSITE_ID        => [
            'rules' => 'required | number',
        ],
        ResourceInterface::PARAM1                => [
            'rules' => 'string',
        ],
        ResourceInterface::PARAM2                => [
            'rules' => 'string',
        ],


    ];

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function executeFunction()
    {
    }

    /**
     * Get results of request
     * @return mixed
     */
    public function getResult()
    {
        $mode                                          = ((app()->configure('app')) ? '' : config('app.debug')) ? 'test' : 'live';
        $configprefix                                  = 'resource_telecombinatie.' . $mode . '_mos_settings';
        $this->params[ResourceInterface::KEY]          = Config::get($configprefix . '.buckaroo_code');
        $this->params[ResourceInterface::PRODUCT_TYPE] = 'simonly3';
        $this->params[ResourceInterface::AMOUNT] = '0.01';
        $this->params[ResourceInterface::CURRENCY] = 'EUR';
        return $this->internalRequest('ideal','payment',$this->params);
    }

}
