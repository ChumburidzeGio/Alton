<?php
namespace App\Resources;

use Log, Config;
use SoapClient;
use SoapHeader;
use SoapVar;


/**
 * Basic WFC Soap Client
 *
 * User: Roeland Werring
 * Date: 16/04/15
 * Time: 11:51
 *
 */
class BasicWFCSoapClient  extends SoapClient
{
    protected $wsdl = '';
    protected $url = '';
    protected $service = '';
    protected $options = null;
    protected $username = null;
    protected $password = null;

    function __construct($options = null)
    {
        $this->options = array_merge(array(
            'trace'              => 0,
            'exceptions'         => true,
            'soap_version'       => SOAP_1_2,
            'connection_timeout' => 3,
            'cache_wsdl'         => WSDL_CACHE_BOTH
        ), is_array($options) ? $options : array());
        parent::__construct($this->wsdl, $this->options);
    }

    function login($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null)
    {
        $headers = array();
        if($this->username && $this->password){
            $headers[] = new SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security',
                new SoapVar(sprintf('<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">' . '<wsse:UsernameToken><wsse:Username>%s</wsse:Username><wsse:Password>%s</wsse:Password></wsse:UsernameToken>' . '</wsse:Security>',
                    htmlspecialchars($this->username), htmlspecialchars($this->password)), XSD_ANYXML));
        }
        $headers[] = new SoapHeader('http://www.w3.org/2005/08/addressing', 'Action', 'http://tempuri.org/ISalesService/AddSelectedInsurance');
        parent::__setSoapHeaders($headers);
        return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
    }

    public function __call($function_name, $arguments)
    {
        return $this->__soapCall($function_name, $arguments[0]);
    }


    public function executeMethod($wsdl, $method, $params, $username = null, $password = null)
    {
        $this->wsdl = $wsdl;
        if ($username && $password){
            $this->login($username, $password);
        }
        return ($this->{$method}($params));
    }
}
