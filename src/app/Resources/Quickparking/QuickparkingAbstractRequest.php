<?php
namespace App\Resources\Quickparking;

use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\Config;


class QuickparkingAbstractRequest extends AbstractMethodRequest
{
    const DATETIME_FORMAT = 'd-m-Y H:i:s';

    const STATUS_CODE_SUCCESS = 10;
    const STATUS_CODE_AUTHORIZATION_DENIED = 400;

    const LANGUAGE_CODE_DUTCH = 'nl-NL';
    const LANGUAGE_CODE_FRENCH = 'fr-FR';

    // resource2 request!
    public $resource2Request = true;

    protected $params = [];
    protected $cacheDays = 1;

    protected $result;

    // Configuration info
    protected $wsdlUrl;
    protected $username;
    protected $password;

    // Internal request/response info
    protected $soapClient;
    protected $rawResult;

    public function __construct($methodName, \SoapClient $soapClient = null)
    {
        $this->methodName = $methodName;

        $this->wsdlUrl = ((app()->configure('resource_quickparking')) ? '' : config('resource_quickparking.settings.wsdlUrl'));
        $this->username = ((app()->configure('resource_quickparking')) ? '' : config('resource_quickparking.settings.username'));
        $this->password = ((app()->configure('resource_quickparking')) ? '' : config('resource_quickparking.settings.password'));

        $this->soapClient = $soapClient ? $soapClient : new \SoapClient($this->wsdlUrl, [
            'trace' => true,
            'exceptions' => true,
        ]);
    }

    /**
		SoapClient throws exceptions *AND* logs an PHP Fatal Error. The error can be suppressed with @,
		but Laravel will still find it in the last error_get_last() in Illuminate/Exception/Handler::handleShutdown.
		PHP 7 has a 'error_clear_last()', but no such luck in PHP 5.x.
		Dirty hack: clear error_get_last() by triggering a error that gets ignored.
		(from : https://github.com/laravel/framework/issues/6618#issuecomment-204728254)
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
        $callParams = [[
            'APIlogin' => $this->username,
            'APIpassword' => $this->password,
        ] + $this->params];

        try
        {
            $soapResult = @$this->soapClient->__soapCall($this->methodName, $callParams);

            $this->result = json_decode(json_encode($soapResult), true);
        }
        catch (\SoapFault $e)
        {
            $this->clearLastSoapFatalError();

            $this->setErrorString('SOAP error: `'. $e->getMessage() .'`');
            return;
        }

        $this->rawResult = $this->result;

        if (isset($this->result[$this->methodName .'Result']['status']) && $this->result[$this->methodName .'Result']['status'] != self::STATUS_CODE_SUCCESS)
        {
            $this->setErrorString('Server returned technical message (code '. $this->result[$this->methodName .'Result']['status'] .'): `'. $this->result[$this->methodName .'Result']['technicalMessage'] .'``');
            return;
        }
    }

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }

    protected function formatDateTime($inputDateTime)
    {
        // We assume the -local- timezone if none is present in $inputDate
        $dateTime = new \DateTime($inputDateTime);

        return $dateTime->format(self::DATETIME_FORMAT);
    }
}