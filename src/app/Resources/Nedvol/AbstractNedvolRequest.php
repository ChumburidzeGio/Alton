<?php
/**
 * User: Roeland Werring
 * Date: 18/05/15
 * Time: 16:49
 *
 */

namespace App\Resources\Nearshoring;


use App\Resources\AbstractMethodRequest;
use App\Resources\BasicWFCSoapClient;

class AbstractNedvolRequest extends AbstractMethodRequest
{

    protected $cacheDays = false;

    protected $method;
    protected $params;
    protected $wsdl;


    /** @var BasicWFCSoapClient $soapClient */
    protected $soapClient;

    public function __construct()
    {
    }


    public function executeFunction()
    {

        $this->soapClient = new BasicWFCSoapClient();
        $this->strictStandardFields = false;
    }

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        $result =  json_decode(json_encode($this->soapClient->executeMethod($this->wsdl, $this->method, $this->params)), true);
        return $result;
        
    }
}