<?php
/**
 * User: Roeland Werring
 * Date: 09/02/17
 * Time: 12:05
 *
 */

namespace App\Resources\Infofolio;


use App\Resources\AbstractMethodRequest;
use SoapClient;
use SoapFault;
use SoapHeader;
use Config;

class AbstractInfofolioRequest extends AbstractMethodRequest
{
    public $resource2Request = true;

    // Internal request/response info
    protected $methodName;
    /** @var \SoapClient */
    protected $soapClient;

    protected $username;
    protected $password;
    protected $wsdlUrl;
    protected $service;
    protected $params;
    protected $result;
    protected $cacheDays = false;
    public $skipDefaultFields = true;

    protected $resultMapping = [
    ];


    public function __construct($methodName = null)
    {
        $this->methodName = $methodName;

        $this->username = ((app()->configure('resource_infofolio')) ? '' : config('resource_infofolio.settings.username'));
        $this->password = ((app()->configure('resource_infofolio')) ? '' : config('resource_infofolio.settings.password'));

        $this->service = ((app()->configure('resource_infofolio')) ? '' : config('resource_infofolio.settings.service'));
        $this->wsdlUrl = $this->service . '?wsdl';

        $this->soapClient = new SoapClient($this->wsdlUrl, [
            'soap_version' => SOAP_1_2,
            'trace'        => true,
            'exceptions'   => true
        ]);
    }

    public function setParams(Array $params)
    {
        $this->params = $params;
    }


    public function executeFunction()
    {

        $soapMethodName = Config::get('resource_infofolio.methods.' . $this->methodName);
        $methodUrl      = ((app()->configure('resource_infofolio')) ? '' : config('resource_infofolio.settings.methods_base')) . $soapMethodName;
        $soapHeaders    = [
            new SoapHeader(((app()->configure('resource_infofolio')) ? '' : config('resource_infofolio.settings.soapheader_namespace')), 'Action', $methodUrl),
            new SoapHeader(((app()->configure('resource_infofolio')) ? '' : config('resource_infofolio.settings.soapheader_namespace')), 'To', $this->service)
        ];
        $this->params['userName'] = $this->username;
        $this->params['password'] = $this->password;
        $this->params['method'] = ((app()->configure('resource_infofolio')) ? '' : config('resource_infofolio.settings.infoset'));
        $this->result = json_decode(json_encode($this->soapClient->__soapCall($soapMethodName, [$this->params], null, $soapHeaders)), true);

    }

    public function __destruct()
    {
        unset($soapClient);
    }
}
?>