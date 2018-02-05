<?php
namespace App\Resources\Schipholparking;

use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\Config;


class SchipholparkingAbstractRequest extends AbstractMethodRequest
{
    // resource2 request!
    public $resource2Request = true;
    protected $params = [];
    protected $cacheDays = 1;
    protected $result;

    protected $defaultMethodName;

    // Configuration info
    protected $username;
    protected $password;
    protected $customerCode;
    protected $agentCode;
    protected $defaultLanguageCode;

    // Internal request/response info
    protected $methodName;
    /** @var \SoapClient */
    protected $soapClient;
    protected $rawResponseXml;

    protected $valetProducts = [
        'PVA', // "Schiphol Valet Parking - voor Privium Plus leden"
        'SVP', // "Schiphol Valet Parking"
        'SVPB', // "Schiphol Holiday Valet Parking"
    ];

    public function __construct($methodName = null, \SoapClient $soapClient = null)
    {
        $this->methodName =  $methodName ? $methodName : $this->defaultMethodName;

        $this->customerCode = ((app()->configure('resource_schipholparking')) ? '' : config('resource_schipholparking.settings.customer_code'));
        $this->agentCode = ((app()->configure('resource_schipholparking')) ? '' : config('resource_schipholparking.settings.agent_code'));
        $this->username = ((app()->configure('resource_schipholparking')) ? '' : config('resource_schipholparking.settings.username'));
        $this->password = ((app()->configure('resource_schipholparking')) ? '' : config('resource_schipholparking.settings.password'));

        $this->wsdlUrl = ((app()->configure('resource_schipholparking')) ? '' : config('resource_schipholparking.settings.wsdlUrl'));

        $this->soapClient = $soapClient ? $soapClient : new \SoapClient(
            ((app()->configure('resource_schipholparking')) ? '' : config('resource_schipholparking.settings.wsdlUrl')),
            [
                'trace' => true,
                'exceptions' => true,
            ]
        );

        $this->defaultLanguageCode = Parking::LANGUAGECODE_DUTCH;
    }

    protected function getParamDefaults()
    {
        return [];
    }

    /**
     * SoapClient throws exceptions *AND* logs an PHP Fatal Error. The error can be suppressed with @,
     * but Laravel will still find it in the last error_get_last() in Illuminate/Exception/Handler::handleShutdown.
     * PHP 7 has a 'error_clear_last()', but no such luck in PHP 5.x.
     * Dirty hack: clear error_get_last() by triggering a error that gets ignored.
     * (from : https://github.com/laravel/framework/issues/6618#issuecomment-204728254)
     */
    public function clearLastSoapFatalError()
    {
        if (!error_get_last() || !starts_with(error_get_last()['message'], 'SOAP-ERROR'))
            return false;

        set_error_handler('var_dump', 0); // Never called because of empty mask.
        @trigger_error('');
        restore_error_handler();

        return true;
    }

    public function executeFunction()
    {
        $inputHeaders = new \SoapHeader(
            ((app()->configure('resource_schipholparking')) ? '' : config('resource_schipholparking.settings.wsdlNamespace')),
            'AuthHeader',
            ['UserName' => $this->username, 'Password' => $this->password]
        );

        try
        {
            $soapResult = $this->soapClient->__soapCall($this->methodName, [$this->params], null, $inputHeaders);

            $this->rawResponseXml = $soapResult->{$this->methodName .'Result'};
        }
        catch (\SoapFault $e)
        {
            cw('MOOOOO');
            cw($this->soapClient->__getLastRequest());
            cw($this->soapClient->__getLastResponse());

            $this->clearLastSoapFatalError();

            $this->setErrorString('SOAP error: `'. $e->getMessage() .'`');
            return;
        }

        cw($this->soapClient->__getLastRequest());
        cw($this->soapClient->__getLastResponse());

        try
        {
            // Actual response data is in a XML string inside CDATA inside the root XML element
            $result = json_decode(json_encode($this->rawResponseXml), true);
        }
        catch (\Exception $e)
        {
            if ($e->getPrevious())
                $this->setErrorString('Could not parse embedded response XML: '. $e->getMessage() .' - '. $e->getPrevious()->getMessage());
            else
                $this->setErrorString('Could not parse embedded response XML: '. $e->getMessage() .' - '. $e->getMessage());
            return;
        }

        // Sometimes an error is in a subelement. Relocate it to be in the expected (root) spot.
        if (isset($result['error'], $result['error']['errorcode'], $result['error']['errortext']))
        {
            $result = $result['error'];
        }

        if (isset($result['errorcode'], $result['errortext']))
        {
            $this->setErrorString('Errorcode '. $result['errorcode'] .': '. $result['errortext'] );
            return;
        }

        $this->result = $result;
    }

    public function getRawResponseXml()
    {
        return $this->rawResponseXml;
    }

    public function setParams(Array $params)
    {
        $defaultParams = $this->getParamDefaults();
        if ($defaultParams !== [])
            $params = array_only($params, array_keys($this->getParamDefaults()));

        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }

    protected function splitDate($inputDate)
    {
        // We assume the Amsterdam timezone if none is present in $inputDate
        $date = new \DateTime($inputDate, new \DateTimeZone('Europe/Amsterdam'));

        // All Schiphol parking API input dates & times must be in the Amsterdam timezone
        $date->setTimezone(new \DateTimeZone('Europe/Amsterdam'));
        return [
            $date->format('dmY'),
            $date->format('Hi'),
        ];
    }

    protected function splitLocationId($locationId)
    {
        $info = explode('|', $locationId);

        return [
            'airportCode' => isset($info[0]) ? $info[0] : '',
            'carparkCode' => isset($info[1]) ? $info[1] : '',
            'productCode' => isset($info[2]) ? $info[2] : '',
        ];
    }

    /**
     * SimpleXML returns empty arrays when an XML tag is empty. (<data></data> or <data />)
     * Can't simply cast to (string), that will PHP-error.
     *
     * @param $input
     * @return string
     */
    protected function removeEmptyArray($input)
    {
        if ($input === [])
            return '';

        return $input;
    }

    protected function getItemArray($items, $name)
    {
        if (!isset($items[$name]))
            return [];

        if (!isset($items[$name][0]) && is_array($items[$name]))
            return [$items[$name]];

        return $items[$name];
    }

    /**
     * @param $addons Array of addon ids, or comma-separated list of addon ids.
     * @return string XML for 'AddsOns' field
     */
    protected function createAddonsXml($addons)
    {
        // Empty string = no addons
        if (is_string($addons) && $addons == '')
            return $addons;

        // If it is already XML, assume it is correct
        if (is_string($addons) && starts_with($addons, '<addons>'))
            return $addons;

        // Process `{id}:{quanity},{id}` format
        if (is_string($addons))
            $addons = explode(',', $addons);
        if (count($addons) == 0)
            return '';
        $addonsXml = '<addons>';
        foreach ($addons as $key => $addOnId)
        {
            $quantity = 1;
            // Quantity for addons in addOnId is in format 'code:quantity'
            // (quantity is 1 if not present)
            if (str_contains($addOnId, ':'))
                list($addOnId, $quantity) = explode(':', $addOnId, 2);

            $addonsXml .= '<addon'. sprintf('%03d', $key + 1).'><code>'. trim($addOnId) .'</code><quantity>'.sprintf('%03d', $quantity).'</quantity></addon'. sprintf('%03d', $key + 1).'>';
        }
        $addonsXml .= '</addons>';

        return $addonsXml;
    }
}