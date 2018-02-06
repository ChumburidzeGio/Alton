<?php
namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;

/**
 * Basic Dummy request
 *
 * User: Roeland Werring
 * Date: 16/04/15
 * Time: 11:51
 *
 */
class AutoSchadevrijeJaren extends AbstractMethodRequest
{
    protected $cacheDays = false;

    private $params = [];
    // we use resource param validation
    public $resource2Request = true;

    //dummy function
    public function setParams(Array $params)
    {
        $this->params = $params;
        return;
    }

    public function getResult()
    {
        $free = $this->params[ResourceInterface::YEARS_INSURED] - $this->params[ResourceInterface::DAMAGES] * 5;
        $result[ResourceInterface::YEARS_WITHOUT_DAMAGE] =  ($free < 0) ? 0 : $free;
        return $result;
    }

    public function executeFunction()
    {
        //do nothing
    }
}
