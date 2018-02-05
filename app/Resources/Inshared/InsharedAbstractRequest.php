<?php
namespace App\Resources\Inshared;

use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\AbstractMethodRequest;
use App\Resources\Inshared\Methods\SessionLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class InsharedAbstractRequest extends AbstractMethodRequest
{


    // Structure names
    const INPUT_METHOD_NAME = 'uitvoeren';
    const INPUT_ARGUMENT_NAME = 'cynosure_input';
    const OUTPUT_RESPONSE_NAME = 'cynosure_response';
    const OUTPUT_MESSAGES_NAME = 'meldingen';
    const OUTPUT_SUCCESS_NAME = 'succesvol_indicatie';
    const OUTPUT_RESULT_NAME = 'resultaat';

    const OUTPUT_ERROR_CODE_NAME = 'boodschap_code';
    const OUTPUT_ERROR_CONTENT_NAME = 'boodschap_inhoud';
    const SESSION_ID_NAME = 'soap_sessie_id';

    // Known / Fixed values
    const CAR_TYPE_ID_UNKNOWN = '1';

    const TRUE_STRING = 'J'; // Ja
    const FALSE_STRING = 'N'; // Nee

    const OPTIONAL_CODE = 'O';  // 'Optioneel'
    const MANDATORY_CODE = 'V'; // 'Verplicht'

    const COVERAGE_TO_MODULE_ID = [
        ResourceValue::CAR_COVERAGE_MINIMUM  => 'WA', // 'WA (verplicht)'
        ResourceValue::CAR_COVERAGE_LIMITED  => 'BC', // 'Beperkt Casco'
        ResourceValue::CAR_COVERAGE_COMPLETE => 'CA', // 'Allrisk'
    ];

    const GENDER_TO_CODE = [
        ResourceValue::MALE   => 'M',
        ResourceValue::FEMALE => 'V',
    ];
    const RELATION_TO_CODE = [
        ResourceValue::PARTNER => 'PARTNER',
        ResourceValue::CHILD   => 'KIND',
    ];
    const FAMILY_COMPOSITION_TO_CODE = [
        ResourceValue::SINGLE_NO_KIDS => '1ZON',
        ResourceValue::SINGLE_WITH_KIDS => '1MET',
        ResourceValue::FAMILY_NO_KIDS => '2ZON',
        ResourceValue::FAMILY_WITH_KIDS => '2MET',
    ];

    // 'up_to' is inclusive.
    protected $mileageGroups = [
        ['up_to' => 15000, 'result' => 14998],
        ['up_to' => 25000, 'result' => 24998],
        ['up_to' => null, 'result' => 25001],
    ];


    // Mapping Inshared Module IDs to Resource types
    protected $moduleIdToResourceValue = [
        'ASVI' => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,      // 'autoverzekering':'Schadeverzekering Inzittenden'
        'PHNL' => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE, // 'autoverzekering':'Pechhulp Nederland'
        'PHEU' => ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE,      // 'autoverzekering':'Pechhulp Europa'
        'ORB'  => ResourceInterface::LEGALEXPENSES_VALUE,                   // 'rechtsbijstandverzekering':'Rechtshulp Ongeval'
        'INVL' => ResourceInterface::ACCIDENT_AND_DISABLED_VALUE,           // 'ongevallenverzekering':'Ongeval & Blijvend letsel'
        'OVRL' => ResourceInterface::ACCIDENT_AND_DEATH_VALUE,              // 'ongevallenverzekering':'Ongeval & Overlijden'
    ];

    // Formats
    const DATE_FORMAT = 'Y-m-d';

    //
    protected $ignoreErrorMessages = false;

    // Resource 2
    public $resource2Request = true;
    protected $cacheDays = 1;

    // Resource input & output storage
    protected $params = [];
    protected $inputParams = null;
    protected $rawResult = null;
    protected $result = null;

    // Client access & Authenication
    protected $soapClient = null;
    protected $wsdlUrl;
    protected $username;
    protected $password;
    protected $partnerId;

    // Session ID fetching
    protected $doingSessionRefresh = false;
    protected $sessionAutoFetch = true;
    protected $sessionAutoRefresh = true;

    // Array for mapping Inshared error message 'source' names to Resource input names.
    protected $mapErrorSourceToInputField = [];
    // Array for mapping specific Inshared error codes to Resource input names.
    protected $mapErrorCodeToInputField = [];
    // One-to-one input mapping
    protected $inputToParamMapping = [];

    public function __construct($wsdlPath, \SoapClient $soapClient = null)
    {
        $this->wsdlUrl   = ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.wsdlBaseUrl')) . $wsdlPath;
        $this->username  = ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.username'));
        $this->password  = ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.password'));
        $this->partnerId = ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.partnerId'));

        $streamContext = stream_context_create([
            'ssl' => [
                'local_cert' => base_path(((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.keyFilePath'))),
                'passphrase' => ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.keyFilePassphrase')),
                'cafile'     => base_path(((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.caCertFilePath'))),
            ]
        ]);

        try {
            $this->soapClient = $soapClient ? $soapClient : new \SoapClient($this->wsdlUrl, [
                'trace'          => true,
                'exceptions'     => true,
                'stream_context' => $streamContext,
            ]);
        } catch(\SoapFault $e) {
            $this->clearLastSoapFatalError();

            // Do not throw exception, setErrorString() handles it.
            $this->setErrorString('Connection to external service failed.');
            Log::warning('Connection InShared API failed: '. $e->getMessage());
        }
    }

    /**
     * SoapClient throws exceptions *AND* logs an PHP Fatal Error. The error can be suppressed with @,
     * but Laravel will still find it in the last error_get_last() in Illuminate/Exception/Handler::handleShutdown.
     * PHP 7 has a 'error_clear_last()', but no such luck in PHP 5.x.
     * Dirty hack: clear error_get_last() by triggering a error that gets ignored.
     * (from : https://github.com/laravel/framework/issues/6618#issuecomment-204728254)
     */
    protected function clearLastSoapFatalError()
    {
        if( ! error_get_last() ) {//|| ! starts_with(error_get_last()['message'], 'SOAP-ERROR')){
            return false;
        }

        set_error_handler('var_dump', 0); // Never called because of empty mask.
        @trigger_error('');
        restore_error_handler();

        return true;
    }

    public function executeFunction()
    {
        $this->errorMessages = [];

        // Auto-fetch session id
        if($this->sessionAutoFetch && isset($this->params[ResourceInterface::SESSION_ID]) && empty($this->params[ResourceInterface::SESSION_ID])){
            $this->params[ResourceInterface::SESSION_ID] = $this->getSessionId();
        }

        // Apply any auth headers
        $inputHeaders = null;
        if(isset($this->params[ResourceInterface::SESSION_ID])){
            $inputHeaders = new \SoapHeader('ns1', 'applicatieHeader', new \SoapVar([self::SESSION_ID_NAME => $this->params[ResourceInterface::SESSION_ID]], SOAP_ENC_OBJECT));
            unset($this->params[ResourceInterface::SESSION_ID]);
        }

        // Build arguments object
        $inputObject                              = new \stdClass();
        $inputObject->{self::INPUT_ARGUMENT_NAME} = $this->params;
        $inputArguments                           = [$inputObject];

        // Do SOAP call
        try{
            //dd($inputArguments);

            $soapResult = @$this->soapClient->__soapCall(self::INPUT_METHOD_NAME, $inputArguments, null, $inputHeaders);

            $this->result = json_decode(json_encode($soapResult), true);
        }
        catch(\SoapFault $e)
        {
            $this->clearLastSoapFatalError();

            if($e->getMessage() == 'Unauthorized' && $this->sessionAutoRefresh){
                $this->doSessionRefresh();

                if( ! $this->getErrorString()){
                    $this->executeFunction();
                }
                return;
            }

            $this->setErrorString('SOAP error: `' . $e->getMessage() . '`');
            return;
        }

        // Process result
        $this->rawResult = $this->result;
        if(isset($this->result[self::OUTPUT_RESPONSE_NAME])){
            $this->result = $this->result[self::OUTPUT_RESPONSE_NAME];
        }

        // Process error messages
        if($this->result[self::OUTPUT_SUCCESS_NAME] != self::TRUE_STRING){
            $this->processInsharedMessages($this->result[self::OUTPUT_MESSAGES_NAME]);
        }

        // Map result
        if(isset($this->result[self::OUTPUT_RESULT_NAME])){
            $this->result = $this->result[self::OUTPUT_RESULT_NAME];
        }
    }

    public function getSessionId($forceRefresh = false)
    {
        $cacheKey = 'resource.request.insharedtoken.' . substr(md5(implode('|', [
                ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.wsdlBaseUrl')),
                ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.username')),
                ((app()->configure('resource_inshared')) ? '' : config('resource_inshared.settings.password')),
            ])), 0, 8);

        if( ! $forceRefresh){
            $cachedSessionId = Cache::tags('resource', 'resource.request')->get($cacheKey);
            if($cachedSessionId !== null){
                return $cachedSessionId;
            }
        }

        $loginRequest = new SessionLogin();
        $loginRequest->setParams([]);
        if($loginRequest->getErrorString()){
            $this->setErrorString('Session login error: ' . $loginRequest->getErrorString());
            return '';
        }
        $loginRequest->executeFunction();
        if($loginRequest->getErrorString()){
            $this->setErrorString('Session login error: ' . $loginRequest->getErrorString());
            return '';
        }
        $loginResult = $loginRequest->getResult();
        if($loginRequest->getErrorString()){
            $this->setErrorString('Session login error: ' . $loginRequest->getErrorString());
            return '';
        }

        $sessionId = $loginResult[ResourceInterface::SESSION_ID];

        Cache::tags('resource', 'resource.request')->forever($cacheKey, $sessionId);

        return $sessionId;
    }

    public function setSessionAutomation($autoFetch = true, $autoRefresh = true)
    {
        $this->sessionAutoFetch   = (bool) $autoFetch;
        $this->sessionAutoRefresh = (bool) $autoRefresh;
    }

    protected function doSessionRefresh()
    {
        // We want to prevent invalid login looping
        if($this->doingSessionRefresh){
            $this->setErrorString('Authentication failed: Session login loop detected.');
            return;
        }

        $this->doingSessionRefresh = true;

        $this->params[ResourceInterface::SESSION_ID] = $this->getSessionId(true);
    }

    public function getDefaultParams()
    {
        return [];
    }

    public function setParams(Array $params)
    {
        if( ! isset($this->inputParams)){
            $this->inputParams = $params;
        }

        $this->params = $params;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    protected function formatDate($inputDateTime)
    {
        // We assume the -local- timezone if none is present in $inputDate
        $dateTime = new \DateTime($inputDateTime);

        return $dateTime->format(self::DATE_FORMAT);
    }

    protected function processInsharedMessages($messages)
    {
        foreach ($this->getItemAsArray($messages) as $message) {
            $field = 'unmapped:' . $message['gegeven'];
            if (isset($this->mapErrorSourceToInputField[$message['gegeven']])) {
                $field = $this->mapErrorSourceToInputField[$message['gegeven']];
            } else if (isset($this->mapErrorCodeToInputField[(string)$message['boodschap_code']])) {
                $field = $this->mapErrorCodeToInputField[$message['boodschap_code']];
            } else {
                foreach ($this->inputToParamMapping as $input => $output) {
                    $outputField = last(explode('.', $output));
                    if ($outputField == $message['gegeven']) {
                        $field = $input;
                        break;
                    }
                }
            }

            $this->addErrorMessage($field, 'inshared.' . $message['boodschap_code'], $message['boodschap_inhoud'], $message['soort']);
        }
        $this->setErrorString('Received ' . count($this->errorMessages) . ' input error message(s) from Inshared server.');

        if ($this->ignoreErrorMessages)
        {
            foreach ($this->errorMessages as $message)
                cw($message['code'] . ' ('. $message['type'] .') '. $message['field'] .'> '. $message['message']);
            cw($this->getErrorString());

            $this->clearErrors();
        }
    }

    /**
     * Converting XML to JSON results in non-array values where multiple are expected but only one is returned.
     *
     * This method handles that by looking to see if there are numeric keys or not, and always return an array.
     *
     * @return array Array of items
     */
    protected function getItemAsArray($input)
    {
        if(isset($input['item']) && is_array($input['item'])){
            $input = $input['item'];
        }

        if($input === null){
            return [];
        }

        if( ! is_array($input)){
            return [$input];
        }

        reset($input);
        $firstKey = key($input);

        // No integer? Associative array = subitem.
        if( ! is_integer($firstKey)){
            return [$input];
        }

        return $input;
    }

    protected function mapInputToParams($inputParams, $params)
    {
        foreach($inputParams as $key => $value){
            if( ! isset($this->inputToParamMapping[$key])){
                continue;
            }

            array_set($params, $this->inputToParamMapping[$key], $value);
        }

        return $params;
    }

    public function getRawResult()
    {
        return $this->rawResult;
    }

    public function mapMileageToGroup($mileage)
    {
        foreach($this->mileageGroups as $mileageGroup){
            if(($mileageGroup['up_to'] === null || $mileage <= $mileageGroup['up_to'])){
                return $mileageGroup['result'];
            }
        }

        throw new \Exception('Cannot map `' . $mileage . '` to a mileage group.');
    }
}