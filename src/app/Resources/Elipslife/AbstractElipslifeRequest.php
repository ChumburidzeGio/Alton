<?php


namespace App\Resources\Elipslife;


use App\Resources\AbstractMethodRequest;

class AbstractElipslifeRequest extends AbstractMethodRequest
{
    public $params = [];
    public $skipDefaultFields = true;
    public $resource2Request  = true;
    protected $cacheDays = false;

    public function setParams(Array $params)
    {
        $this->params = $params;
    }
    public function executeFunction()
    {
        //parent::executeFunction(); //??
    }

    public function getResult()
    {
        return $this->result;
    }

}