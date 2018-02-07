<?php
namespace App\Resources\Allinone;

use App\Helpers\ResourceHelper;
use App\Resources\AbstractMethodRequest;

/**
 * Get carinsurance product information, including possible coverage combinations, possible own risk values and possible mileage values.
 */
class AbstractAllinoneRequest extends AbstractMethodRequest
{
    protected $cacheDays = -1;
    public $resource2Request = true;

    protected $params;
    protected $result;

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }
}