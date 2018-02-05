<?php
/**
 * RollsSoapClient (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 10-aug-2010
 * Time: 18:52:59
 */


namespace App\Resources\Ipparking;

use SoapClient, Config;
use SoapHeader;
use SoapVar;

class IpparkingSoapClient extends SoapClient
{
    protected $wsdl = '';
    protected $service = '';
    protected $options = null;
    protected $username = null;
    protected $password = null;

    function __construct($options = null)
    {
        $url           = ((app()->configure('resource_ipparking')) ? '' : config('resource_ipparking.settings.service'));
        $this->wsdl = $url . '?wsdl';
        $this->service = $url .'/Basic';

        $this->options = array_merge(array(
            'trace'              => 1,
            'exceptions'         => true,
            'soap_version'       => SOAP_1_1,
            'connection_timeout' => 3,
            'cache_wsdl'         => WSDL_CACHE_NONE
        ), is_array($options) ? $options : array());

        parent::__construct($this->wsdl, $this->options);
    }

    function login($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    function __doRequest($request, $location, $action, $version, $one_way = null)
    {
        $args = func_get_args();
        if($this->service){
            $args[1] = $location = $this->service;
        }

        return $this->__cUrlRequest($request, $location, $action, $version);
    }

    function __soapCall($function_name, $arguments, $options = null, $input_headers = null, &$output_headers = null)
    {
        $headers = array();
        if($this->username && $this->password){
            $headers[] = new SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'Security',
                new SoapVar(sprintf('<wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">' . '<wsse:UsernameToken><wsse:Username>%s</wsse:Username><wsse:Password>%s</wsse:Password></wsse:UsernameToken>' . '</wsse:Security>',
                    htmlspecialchars($this->username), htmlspecialchars($this->password)), XSD_ANYXML));
        }

        $headers[] = new SoapHeader('http://www.w3.org/2005/08/addressing', 'Action', 'http://IPParking.nl/ParkBase/central-parking-gateway');

        $headers[] = new SoapHeader('http://www.w3.org/2005/08/addressing', 'To', $this->service);
        $headers[] = new SoapHeader('http://www.w3.org/2005/08/addressing', 'MessageID', 'uuid:f2b36991-a78d-498a-b297-98441f3fc666');
        parent::__setSoapHeaders($headers);

        return parent::__soapCall($function_name, $arguments, $options, $input_headers, $output_headers);
    }

    public function __call($function_name, $arguments)
    {
        return $this->__soapCall($function_name, $arguments); //array(array( 'request' => $arguments )) );
    }

    function __cUrlRequest($request, $location, $action)
    {
        $parsed  = parse_url($location);
        $headers = array(sprintf('User-Agent: Jakarta Commons-HttpClient/3.1'), sprintf('Host: %s', $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '')));
        if($this->options['soap_version'] == SOAP_1_2){
            $headers[] = sprintf('Content-Type: application/soap+xml; charset=UTF-8; action="%s"', $action);
        }else{
            $headers[] = sprintf('Content-Type: text/xml; charset=UTF-8;');
            $headers[] = sprintf('Soapaction: %s', $action);
        }
        $headers[] = sprintf('Content-Length: %s', strlen($request));
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_URL, $location);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $s = curl_exec($ch);

        return $s;
    }

    public function executeMethod($method, $params)
    {
        $this->login(((app()->configure('resource_ipparking')) ? '' : config('resource_ipparking.settings.username')),((app()->configure('resource_ipparking')) ? '' : config('resource_ipparking.settings.password')));
        $params['ResellerCode'] = ((app()->configure('resource_ipparking')) ? '' : config('resource_ipparking.settings.reseller_code'));
        $inString = lcfirst($method).'In';
        return ($this->{$method}([$inString => $params]));
    }
}

//Reseller code: 3b138ca0-2886-47ba-bf7b-e1de26a00024
//Locatie: 4d8111cc-da2f-e211-aa49-68b599c2cc67
//Faciliteiten:
//
//b2bd4efe-1081-e211-beb4-68b599c2cc67
//9407517f-e083-e111-8133-68b599c2cc67
//5837670e-1181-e211-beb4-68b599c2cc67
//55b77782-f094-e311-8198-68b599c2cc66

?>
