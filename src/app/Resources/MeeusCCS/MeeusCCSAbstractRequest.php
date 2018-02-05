<?php
namespace App\Resources\MeeusCCS;

use App\Resources\MappedHttpMethodRequest;
use Config;


class MeeusCCSAbstractRequest extends MappedHttpMethodRequest
{
    protected $soapClient = null;
    protected $methodName = null;

    public $resource2Request = true;

    protected $account;
    protected $username;
    protected $password;
    protected $companyNumber;
    protected $intermediaryNumber;

    protected $params = [];
    protected $inputParams = null;
    protected $result;

    protected $rawResponseXml = null;

    public function __construct($wsdlFile)
    {
        $this->customerCode = ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.customer_code'));
        $this->account = ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.account'));
        $this->username = ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.username'));
        $this->password = ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.password'));
        $this->companyNumber = ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.company_number'));
        $this->intermediaryNumber = ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.intermediary_number'));


        $this->soapClient = new \SoapClient(
            base_path($wsdlFile),
            [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE, // We already have it on disk
            ]
        );
    }

    public function executeFunction()
    {
        // Authentication Header
        $inputHeaders = new \SoapHeader(
            'http://schemas.ccs.nl/soap',
            'header',
            (object)[
                'account' => $this->account,
                'naam' => $this->username,
                'wachtwoord' => $this->password,
                'bedrijfsnummer' => $this->companyNumber,
                'tussenpersoonnummer' => $this->intermediaryNumber,
            ]
        );

        try
        {
            $soapResult = $this->soapClient->__soapCall($this->methodName, [$this->params], null, $inputHeaders);

            $resultName = $this->methodName .'Result';

            $this->rawResponseXml = $soapResult->{$resultName};
        }
        catch (\SoapFault $e)
        {
            cw($this->soapClient->__getLastRequest());
            cw($this->soapClient->__getLastResponse());

            $this->clearLastSoapFatalError();

            $detailMessage = isset($e->detail, $e->detail->error->Description) ? 'Detail message: `'. $e->detail->error->Description .'`' : 'Error response : '. $this->soapClient->__getLastResponse();

            $this->setErrorString('SOAP error: `'. $e->getMessage() .'` - '. $detailMessage);
            return;
        }

        cw($this->soapClient->__getLastRequest());
        cw($this->soapClient->__getLastResponse());

        try
        {
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

        $this->result = $result;
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
}