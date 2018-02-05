<?php
/**
 * RollsSoapClient (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 10-aug-2010
 * Time: 18:52:59
 */


namespace App\Resources\Nearshoring;

use App\Interfaces\ResourceInterface;
use SoapClient, Config;
use SoapHeader;
use SoapVar;

class NearshoringSoapClient extends SoapClient
{
    protected $wsdl = '';
    protected $service = '';
    protected $options = null;
    protected $username = null;
    protected $password = null;

    function __construct($options = null)
    {
        $url        = ((app()->configure('resource_nearshoring')) ? '' : config('resource_nearshoring.settings.service'));
        $this->wsdl = $url . '?wsdl';
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


    public function executeMethod($method, $params)
    {
        $this->login(((app()->configure('resource_nearshoring')) ? '' : config('resource_nearshoring.settings.username')), ((app()->configure('resource_nearshoring')) ? '' : config('resource_nearshoring.settings.password')));
        return ($this->{$method}($params));
    }
}

//<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:tem="http://tempuri.org/" xmlns:core="http://schemas.datacontract.org/2004/07/Core.Sales.Model.ServiceModel">
//   <soap:Header/>
//   <soap:Body>
//      <tem:AddSelectedInsurance>
//         <!--Optional:-->

//         <tem:request>
//            <core:Collective>1000</core:Collective>
//            <core:Persons>
//               <!--Zero or more repetitions:-->
//               <core:Person>
//                  <!--Optional:-->
//                  <core:Birthdate>1991-02-02</core:Birthdate>
//                  <!--Optional:-->
//                  <core:Packages>
//                     <!--Zero or more repetitions:-->
//                     <core:Package>
//                        <core:PackageId>SV1R001R1</core:PackageId>
//                     </core:Package>
//                  </core:Packages>
//                  <!--Optional:-->
//                  <core:PersonType>PolicyOwner</core:PersonType>
//               </core:Person>
//            </core:Persons>
//         </tem:request>
//      </tem:AddSelectedInsurance>
//   </soap:Body>
//</soap:Envelope>

?>
