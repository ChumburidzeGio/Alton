<?php


namespace App\Resources;

use App;
use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceRequestInterface;
use App\Models\ServiceCache;
use Cache;
use Carbon\Carbon;
use Event;
use Komparu\Input\Behaviour\Validatable;
use Komparu\Input\Contract\Validator;
use Komparu\Schema\Contract\Registry;
use PhpSpec\Exception\Exception;
use ReflectionClass;


/**
 * abstract_soap_request (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 10-aug-2010
 * Time: 18:29:25
 */
class AbstractMethodRequest implements ResourceRequestInterface, Validatable
{
    const RESOURCE_BASE_URL = '/v1/resource/';
    const DEFAULT_LINK = 'http://www.komparu.com';

    /**
     * Some standard validation patterns
     */
    const VALIDATION_DATE = 'date';
    const VALIDATION_REQUIRED_DATE = 'required | date';
    const VALIDATION_REQUIRED_EMAIL = 'required | email';
    const VALIDATION_BOOLEAN = 'in:true,false,1,0';
    const VALIDATION_ACTIVATE = 'in:1,0'; // Hack to get a non-required accept element
    const VALIDATION_REQUIRED_BOOLEAN = 'required | in:false,true,0,1';
    const VALIDATION_REQUIRED_COUNTRY_CODE = 'required | choice:BE=Belgie,NL=Nederland,DE=Duitsland,FR=Frankrijk';
    const VALIDATION_LICENSEPLATE = 'licenseplate';
    const VALIDATION_REQUIRED_LICENSEPLATE = 'required | licenseplate';
    const VALIDATION_POSTAL_CODE = 'postalcode';
    const VALIDATION_REQUIRED_POSTAL_CODE = 'required | postalcode';
    const VALIDATION_EXTERNAL_LIST = 'in:%EXTERNAL_LIST%';
    const VALIDATION_REQUIRED_EXTERNAL_LIST = 'required | in:%EXTERNAL_LIST%';
    const VALIDATION_REQUIRED_CURRENCY = 'required | choice:EUR=Euro,US=Dollar';
    const VALIDATION_REQUIRED_BANK = 'required | choice:ABNANL2A=ABN AMRO,ASNBNL21=ASN Bank,INGBNL2A=ING,RABONL2U=Rabobank,SNSBNL2A=SNS Bank,RBRBNL21=RegioBank,TRIONL2U=Triodos Bank,FVLBNL22=Van Lanschot,KNABNL2H=Knab bank';
    const VALIDATION_JA_NEE = 'choice:Ja=Ja,Nee=Nee';
    const VALIDATION_NEE_JA = 'choice:Nee=Nee,Ja=Ja';
    const VALIDATION_MIST_U_TANDEN = 'choice:1to4=1 t/m 4,5ormore=5 of meer, none=Nee';
    const VALIDATION_HOEVEEL_KRONEN = 'choice:0to4=4 of minder,5to9=5 t/m 9,10ormore=10 of meer';
    const VALIDATION_HOEVEEL_BRUGGEN = 'choice:1=1 brug,2ormore=2 of meer bruggen';
    const VALIDATION_HOEVEEL_OUD_KRONEN = 'choice:0to1=0 of 1,2ormore=Meer dan 1';
    const VALIDATION_WAAR_GEHOLPEN = 'choice:paradontoloog=Bij een paradontoloog,mondhygienist=Bij een mondhygienist';
    const VALIDATION_TANDVOORZIENINGEN = 'choice:kroon=Kroon en/of stifttand,brug=Brug,prothese=(Gedeeltelijke) prothese of plaatje of frame';
    const VALIDATION_WORTELKANAALBEHANDELING = 'choice:wortelkanaalbehandeling=Ja een wortelkanaalbehandeling aan meer dan 2 tanden of kiezen (er is geen kroon of brug geplaatst),tandvleesbehandeling=Ja een uitgebreide tandvleesbehandeling (gedeeltelijke) prothese of plaatje of frame';
    const VALIDATION_TANDARTS_BEHANDELINGEN = 'choice:wortelkanaalbehandeling=Wortelkanaalbehandeling,tandvleesbehandeling=Uitgebreide tandvleesbehandeling,implantaat=Implantaat,kroon=Kroon of brug of inlay,vullingen=4 of meer vullingen,kunstgebit=Geheel of gedeeltelijk kunstgebit';
    const VALIDATION_HOURS = 'in:00:00:00,01:00:00,02:00:00,03:00:00,04:00:00,05:00:00,06:00:00,07:00:00,08:00:00,09:00:00,10:00:00,11:00:00,12:00:00,13:00:00,14:00:00,15:00:00,16:00:00,17:00:00,18:00:00,19:00:00,20:00:00,21:00:00,22:00:00,23:00:00';
    const VALIDATION_REQUIRED_HOURS = 'required | in:00:00:00,01:00:00,02:00:00,03:00:00,04:00:00,05:00:00,06:00:00,07:00:00,08:00:00,09:00:00,10:00:00,11:00:00,12:00:00,13:00:00,14:00:00,15:00:00,16:00:00,17:00:00,18:00:00,19:00:00,20:00:00,21:00:00,22:00:00,23:00:00';
	const VALIDATION_QUARTER_HOURS = 'in:00:00:00,00:15:00,00:30:00,00:45:00,01:00:00,01:15:00,01:30:00,01:45:00,02:00:00,02:15:00,02:30:00,02:45:00,03:00:00,03:15:00,03:30:00,03:45:00,04:00:00,04:15:00,04:30:00,04:45:00,05:00:00,05:15:00,05:30:00,05:45:00,06:00:00,06:15:00,06:30:00,06:45:00,07:00:00,07:15:00,07:30:00,07:45:00,08:00:00,08:15:00,08:30:00,08:45:00,09:00:00,09:15:00,09:30:00,09:45:00,10:00:00,10:15:00,10:30:00,10:45:00,11:00:00,11:15:00,11:30:00,11:45:00,12:00:00,12:15:00,12:30:00,12:45:00,13:00:00,13:15:00,13:30:00,13:45:00,14:00:00,14:15:00,14:30:00,14:45:00,15:00:00,15:15:00,15:30:00,15:45:00,16:00:00,16:15:00,16:30:00,16:45:00,17:00:00,17:15:00,17:30:00,17:45:00,18:00:00,18:15:00,18:30:00,18:45:00,19:00:00,19:15:00,19:30:00,19:45:00,20:00:00,20:15:00,20:30:00,20:45:00,21:00:00,21:15:00,21:30:00,21:45:00,22:00:00,22:15:00,22:30:00,22:45:00,23:00:00,23:15:00,23:30:00,23:45:00';
    const VALIDATION_REQUIRED_QUARTER_HOURS = 'required | in:00:00:00,00:15:00,00:30:00,00:45:00,01:00:00,01:15:00,01:30:00,01:45:00,02:00:00,02:15:00,02:30:00,02:45:00,03:00:00,03:15:00,03:30:00,03:45:00,04:00:00,04:15:00,04:30:00,04:45:00,05:00:00,05:15:00,05:30:00,05:45:00,06:00:00,06:15:00,06:30:00,06:45:00,07:00:00,07:15:00,07:30:00,07:45:00,08:00:00,08:15:00,08:30:00,08:45:00,09:00:00,09:15:00,09:30:00,09:45:00,10:00:00,10:15:00,10:30:00,10:45:00,11:00:00,11:15:00,11:30:00,11:45:00,12:00:00,12:15:00,12:30:00,12:45:00,13:00:00,13:15:00,13:30:00,13:45:00,14:00:00,14:15:00,14:30:00,14:45:00,15:00:00,15:15:00,15:30:00,15:45:00,16:00:00,16:15:00,16:30:00,16:45:00,17:00:00,17:15:00,17:30:00,17:45:00,18:00:00,18:15:00,18:30:00,18:45:00,19:00:00,19:15:00,19:30:00,19:45:00,20:00:00,20:15:00,20:30:00,20:45:00,21:00:00,21:15:00,21:30:00,21:45:00,22:00:00,22:15:00,22:30:00,22:45:00,23:00:00,23:15:00,23:30:00,23:45:00';

    const EXTERNAL_LIST_KEY = '%EXTERNAL_LIST%';

    const VALIDATION_EXTERNAL_CHOICE = 'choice:%EXTERNAL_CHOICE%';
    const VALIDATION_REQUIRED_EXTERNAL_CHOICE = 'required | choice:%EXTERNAL_CHOICE%';

    const EXTERNAL_CHOICE_KEY = '%EXTERNAL_CHOICE%';


    /**
     * Amount of days this request is cached in database
     * @var int
     */
    protected $cacheDays = 365;

    /**
     * Arguments, each argument can contain, rules, example and filters
     * @var array
     */
    protected $arguments = [];

    /**
     * Custom error to be set
     * @var
     */
    protected $customError;
    protected $errorMessages = [];
    protected $prettyError;
    protected $debug = false;


    /**
     * @Mappings do not touch this, only to pass through from service request
     */
    protected $fieldMapping;
    protected $filterMapping;
    protected $filterKeyMapping;

    /**
     * function path, for instance 'car/licenseplate'. Only needed for cache key creation
     */
    protected $path;

    /**
     * Validator, passed through from controller
     */
    protected $validator;


    /**
     * Defines wheter this request should be linked to a document object
     * @var bool
     */
    protected $documentRequest = false;

    /**
     * Defines wheter this request can be used as a funnel
     * @var bool
     */
    protected $funnelRequest = false;

    /**
     * Defines wheter this request should be used to populate products (used on for instancer policy details).
     * @var bool
     */
    protected $populateRequest = false;


    /**
     * Defines if we should only return standard fields as defined in the ResourceInterface
     * @var bool
     */
    protected $strictStandardFields = true;


    /**
     * Defines if we should ignore the default fields as listed in AbstractServiceRequest
     * @var bool
     */
    public $skipDefaultFields = false;


    /**
     * When we use param validation from Resource, we should skip default functions in order to let all params through. Ultimately this should be true for all functions.
     */
    public $resource2Request = false;


    /**
     * Meta data of the request
     * @var array
     */
    protected $meta = [];


    /**
     * Array of output fields
     * @var array
     */
    protected $outputFields = [];


    //Options:
    //internal
    //database
    protected $cacheType = 'database';


    //zanox, telecombinatie, etc
    protected $serviceproviderName;

    /**
     * request conditions passed by document controller will be available here
     */
    protected $conditions = [];


    protected $defaultParamsFilter = false;


    /**
     * @param Validator $validator
     *
     * @return array
     */

    public function arguments(Validator $validator = null)
    {
        //set validator, we need to pass this through as
        if($validator){
            $this->validator = $validator;
        }

        //add logic to generate arguments out of externa list
        foreach($this->arguments as $key => $argument){
            if(isset($argument) && isset($argument['rules']) && stripos($argument['rules'], self::EXTERNAL_LIST_KEY) !== false && isset($argument['external_list'])){
                //for arguments we use only internal cache for speed!!
                $res  = $this->internalRequest($argument['external_list']['resource'], $argument['external_list']['method'], $argument['external_list']['params']);
                $args = [];
                //create the options
                foreach($res as $row){
                    $rowVal = $row[$argument['external_list']['field']];
                    $args[] = (is_bool($rowVal) && $rowVal == false) ? 'false' : $rowVal;
                }
                //update rules
                $this->arguments[$key]['rules']   = str_ireplace(self::EXTERNAL_LIST_KEY, implode(',', $args), $argument['rules']);
                $this->arguments[$key]['example'] = $args;
            }

            if(isset($argument) && isset($argument['rules']) && stripos($argument['rules'], self::EXTERNAL_CHOICE_KEY) !== false && isset($argument['external_choice'])){
                //for arguments we use only internal cache for speed!!
                $res      = $this->internalRequest($argument['external_choice']['resource'], $argument['external_choice']['method'], $argument['external_choice']['params']);
                $ruleArgs = [];
                $args     = [];
                //create the options
                foreach($res as $row){
                    if( ! isset($row[$argument['external_choice']['key']])){
                        continue;
                    }

                    $rowVal = $row[$argument['external_choice']['val']];
                    $rowKey = $row[$argument['external_choice']['key']];

                    $args[$rowKey] = $rowVal == false ? 'false' : $rowVal;
                    $ruleArgs[]    = $rowKey . '=' . $rowVal;
                }
                //update rules
                $this->arguments[$key]['rules']   = str_ireplace(self::EXTERNAL_CHOICE_KEY, implode(',', $ruleArgs), $argument['rules']);
                $this->arguments[$key]['example'] = array_keys($args);
            }
        }

        return $this->arguments;
    }

    public function cache()
    {
        return $this->cacheDays;
    }

    public function outputFields()
    {
        return $this->outputFields;
    }

    public function debug()
    {
        return $this->debug;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    //dummy function
    public function setParams(Array $params)
    {
        return;
    }


    /**
     * Filter params value based on their filter setting
     *
     * @param array $params
     */
    public function escapeParams(Array $params)
    {
        $return = [];
        foreach($params as $key => $value){
            $return[$key] = $this->escapeBrackets($value);
        }
        return $return;
    }

    /**
     * Filter params value based on their filter setting
     *
     * @param array $params
     */
    public function filterParams(Array $params)
    {
        $return = [];
        foreach($params as $key => $value){
            //            echo "checking ".$key.PHP_EOL;
            //apply filters on value
            if(isset($this->arguments[$key]) && isset($this->arguments[$key]['filter'])){
                $filters = explode(',', $this->arguments[$key]['filter']);
                foreach($filters as $filter){
                    $value = ResourceFilterHelper::$filter($value);
                }
                $return[$key] = $value;
                continue;
            }
            $return[$key] = $value;
        }
        return $return;
    }

    /**
     * Filter params value based on their filter setting
     *
     * @param array $params
     */
    public function filterParamKeys(Array $params)
    {
        $return = [];
        foreach($params as $key => $value){
            //apply filters on value
            if(isset($this->filterKeyMapping[$key])){
                $return[$this->filterKeyMapping[$key]] = $value;
                continue;
            }
            $return[$key] = $value;
        }
        return $return;
    }


    /**
     * Set default params if not filled
     *
     * @param array $params
     */
    public function defaultParams(Array $params)
    {
        if($this->resource2Request){
            return $params;
        }
        $return    = [];
        $arguments = $this->arguments();

        foreach($params as $key => $value){
            if(is_string($this->defaultParamsFilter) && str_contains($key, $this->defaultParamsFilter)){
                $return[$key] = $value;
            }
        }

        foreach($arguments as $key => $argument){
            if( ! array_has($params, $key)){
                if(isset($arguments[$key]['default'])){
                    $return[$key] = $arguments[$key]['default'];
                    continue;
                }
            }else{
                $return[$key] = array_get($params, $key);
            }
        }
        return $return;
    }


    public function setErrorString($string)
    {
        $this->customError = $string;
    }

    public function hasErrors()
    {
        return (($this->customError) || count($this->errorMessages) || ($this->prettyError));
    }


    public function clearErrors()
    {
        $this->customError   = null;
        $this->errorMessages = [];
    }

    /**
     * @param $field 'smscode' or array
     * @param $code 'code.message.wrong' => "Het veld {{field}} is niet zo best"
     * @param $message 'standaard translations'
     * @param $type
     */
    public function addErrorMessage($field = null, $code, $message, $type = null)
    {
        $this->errorMessages[] = [
            'code'    => $code,
            'field'   => $field,
            'message' => $message,
            'type'    => $type,
        ];
    }


    public function getErrorString()
    {
        return $this->customError;
    }

    public function getPrettyErrorString(){
        return $this->prettyError;
    }

    public function setPrettyErrorString( $prettyErrorString = 'An error has occured.'){
        $this->prettyError = $prettyErrorString;
    }

    /**
     * Get the rules for a document.
     * @var array
     * @return array
     */
    public function rules()
    {
        $rules = [];
        foreach($this->arguments() as $field => $options){
            $rules[$field] = $this->escapeBrackets($options['rules']) . "";
        }
        return $rules;
    }

    /**
     * Remove all 'required' rules for all fields.
     * This is useful for updating, when no field
     * is actually required.
     */
    public function removeRequired()
    {
        // TODO: Implement removeRequired() method.
    }

    /**
     * If there are rules that don't exist in the data, we don't
     * want them to be evaluated. This is only the case when
     * updating an existing document. This method allows the
     * rules to be removed from the validator, so only the values
     * in the data will be checked.
     *
     * @param array $data
     */
    public function matchRulesWithData(Array $data)
    {
        // TODO: Implement matchRulesWithData() method.
    }

    public function executeFunction()
    {
        $this->setErrorString("Not implemented for this method [executeFunction]");
    }

    /**
     * Get results of request
     * @return mixed
     */
    public function getResult()
    {
        $this->setErrorString("Not implemented for this method [getResult]");
    }

    public function getMeta()
    {
        return $this->meta;
    }


    function getNow()
    {
        return date("Ymd");
    }

    /**
     * Function to store the cache
     *
     * @param $function
     * @param $key
     * @param $value
     */
    public function storeCache($function, $key, $value)
    {
        if($this->cacheType == 'database'){
            $sCache        = ServiceCache::firstOrCreate(['function' => $function, 'key' => $key]);
            $sCache->value = json_encode($value, JSON_NUMERIC_CHECK);
            $sCache->save();
            $sCache->touch();
            return;
        }
        if($this->cacheType == 'internal'){
            Cache::tags('resource', 'resource.request')->forever('resource.request' . $function . ':' . $key, json_encode($value, JSON_NUMERIC_CHECK));
            return;
        }
    }


    /**
     * @param $function
     * @param $key
     *
     * @return static
     */
    public function retrieveCache($function, $key)
    {

        //dd("Resource cache retrieve 'function' => $function, 'key' => $key");
        //    Cache::tags('webservice', 'webservice.info')->flush();
        if($this->cacheType == 'internal'){
            $cache = Cache::tags('resource', 'resource.request')->get('resource.request' . $function . ':' . $key);
            if($cache == null){
                return false;
            }

            return json_decode($cache, true);
        }

        $cacheLength = $this->cache();
        if( ! $cacheLength){
            return false;
        }
        $sCache = ServiceCache::firstOrNew(['function' => $function, 'key' => $key]);
        if($sCache->id){
            $updatedAt = $sCache->updated_at;
            if(Carbon::now()->lte($updatedAt->addDays($cacheLength))){
                cw("Retrieved resource from cache  'function' => $function, 'key' => $key");
                return json_decode($sCache->value, true);
            }
        }
        cw("Resource cache miss 'function' => $function, 'key' => $key");
        return false;
    }

    public function call(Array $params, $path, Validator $validator, Array $fieldMapping, Array $filterKeyMapping, Array $filterMapping, $serviceproviderName)
    {
        $initialParams             = $params;
        $this->validator           = $validator;
        $this->path                = $path;
        $this->fieldMapping        = $fieldMapping;
        $this->filterMapping       = $filterMapping;
        $this->filterKeyMapping    = $filterKeyMapping;
        $this->serviceproviderName = $serviceproviderName;

        //check if debug mode is on, and unset param for future usage
        if(isset($params['debug']) && $params['debug'] != 'false'){
            $this->setDebug(true);
        }
        unset($params['debug']);

        //check if debug mode is on, and unset param for future usage
        if(isset($params['c'])){
            $this->conditions = $params['c'];
        }
        unset($params['c']);

        //check if debug mode is on, and unset param for future usage
        $skipCache = false;
        if(isset($params['skipcache']) && $params['skipcache'] != 'false'){
            $skipCache = true;
        }
        unset($params['skipcache']);


        //force updating arguments and rules from external list
        $this->arguments();

        //load client in validator
        $this->validator->setRules($this);

        //$params = $this->escapeParams($params);
        //validate input
        if( ! $this->validator->validate($this->escapeBrackets($params))){

            foreach($this->validator->messages() as $field => $messages){
                foreach($messages as $message){
                    $this->addErrorMessage($field, 'validator.' . substr(md5($message), 0, 8), $message, 'input');
                }
            }

            return $this->returnError();
        }

        //set default params if not filled

        $params = $this->defaultParams($params);


        $defaultParams = $this->defaultParams($params);


        //filter the params values
        $params = $this->filterParams($params);


        //create a cache key
        $cacheKey = $this->createKey($params);

        //TODO: ADD PROPER CACHE IMPLEMENTATION HERE
        //check if cache enabled, and if so retrieve values
//        if($skipCache or $this->debug or ($requestResult = $this->retrieveCache($this->path, $cacheKey)) === false){
//
//        }
        cws('external_resource', 'External resource request');
        //start execution
        //filter the params $keys

        $params = $this->filterParamKeys($params);
        $this->setParams($params);


        //soort porno
        if($this->hasErrors()){
            return $this->returnError();
        }

        $this->executeFunction();
        if($this->hasErrors()){
            return $this->returnError();
        }


        $result = $this->getResult();


        if($this->hasErrors()){
            return $this->returnError();
        }


        //process results
        $result = $this->processResult($result);


        //set defaults for missing output fields
        $result = $this->setDefaultOutputfields($result);
        //get optional request meta data;
        $meta          = $this->getMeta();
        $requestResult = ['result' => $result, 'meta' => $meta, 'input' => $params];

        //store in cache if enabled
        //if( ! $this->debug && ($cacheDays = $this->cache()) !== false){
        //again load from table, cause in case we of concurrent request, we have small change on double record

//        if($this->cacheDays){
//            $this->storeCache($this->path, $cacheKey, $requestResult);
//        }
        //}
        cwe('external_resource');
        $result = new \ArrayObject($requestResult);
        Event::fire('resource.after', [$this, $result, $defaultParams]);
        Event::fire('resource.' . $this->getRequestType() . '.after', [$result, $initialParams]);
        Event::fire('resource.' . str_replace('/', '.', $this->path) . '.after', [$result, $initialParams]);
        return $result->getArrayCopy();
    }


    private function setDefaultOutputfields($result)
    {

        if(empty($this->outputFields)){
            return $result;
        }
        foreach($result as $key => &$res){
            if(is_array($res)){
                foreach($this->outputFields as $outputfield){
                    if( ! isset($res[$outputfield])){
                        //integer,boolean,price,string,stranslation,text
                        switch($outputfield){
                            case 'price':
                            case 'integer':
                                $res[$outputfield] = 0;
                                break;
                            case 'string':
                            case 'stranslation':
                                $res[$outputfield] = "";
                                break;
                            case 'boolean':
                                $res[$outputfield] = "false";
                                break;
                            default:
                                $res[$outputfield] = 0;
                        }
                    }
                }
            }
            // die;
        }
        return $result;

    }


    /**
     * Create a simple cache key from params
     *
     * @param $params
     *
     * @return mixed
     */
    protected function createKey($params)
    {
        return str_replace('-', '', strtolower(json_encode($params, JSON_NUMERIC_CHECK)));
    }

    /**
     * @param $message
     *
     * @param null $errorArr
     *
     * @return string
     */
    protected function returnError()
    {
        $err = [];
        if(count($this->errorMessages)){
            $err['error_messages'] = $this->errorMessages;
        }

        if($this->customError){
            /**
             * This shit should be really removed
             */
            $trans = [
                'This field is required'      => 'Dit veld is verplicht',
                'This input must be a number' => 'Dit veld moet bestaan uit cijfers'
            ];

            //megahack
            if(is_array($this->customError)){
                $retArr = [];
                foreach($this->customError as $key => $val){
                    foreach($trans as $transkey => $transval){
                        if(is_array($val)){
                            foreach($val as $valkey => $valval){
                                $retArr[$key][$valkey] = str_replace($transkey, $transval, isset($retArr[$key][$valkey]) ? $retArr[$key][$valkey] : $valval);

                            }
                        }else{
                            $retArr[$key] = str_replace($transkey, $transval, $val);
                        }
                    }
                }
                $message = $retArr;
            }
            $err['error'] = $this->customError;
        }
        if($this->prettyError){
            $err['pretty_error'] = $this->prettyError;
        }

        return $err;
    }


    /**
     * Make sure we only work with arrays not objects
     * Replace keys by mapping
     *
     * @param $object the object
     *
     * @return Array encoded string
     */
    protected function processResult($res)
    {
        //        Make sure we have a clean array
        //        $result = json_decode(json_encode($res, JSON_NUMERIC_CHECK), true);
        //        $result = json_encode($res, JSON_NUMERIC_CHECK);
        $result = is_object($res) ? json_decode(json_encode($res, JSON_NUMERIC_CHECK), true) : $res;
        //if node is an array, apply recursion
        if(is_array($result)){
            foreach($result as $key => $val){
                $newVal = $this->processResult($val);
                $newKey = $key;

                //replace the key by field map
                if(isset($this->fieldMapping[$key])){
                    unset($result[$key]);
                    $result[$this->fieldMapping[$key]] = is_numeric($newVal) ? (float) $newVal : $newVal;
                    $newKey                            = $this->fieldMapping[$key];
                }else{
                    $result[$newKey] = $newVal;
                }

                //check if field should be in the result
                if( ! $this->debug && $this->strictStandardFields && ! $this->resource2Request && ! $this->isStandardField($newKey)){
                    unset($result[$newKey]);
                    continue;
                }

                //check if filter is set and apply it
                if(isset($this->filterMapping[$newKey])){
                    $functionName    = $this->filterMapping[$newKey];
                    $result[$newKey] = ResourceFilterHelper::$functionName($newVal);
                }
            }
            return $result;
        }
        if(isset($this->fieldMapping[$result])){
            return $this->fieldMapping[$result];
        }
        return $result;
    }


    /**
     * This simply checks if this key is listed among the constants, i.e. if it is a standard field
     *
     * @param $checkKey
     *
     * @return bool
     */
    private function isStandardField($checkKey)
    {
        if(is_int($checkKey)){
            return true;
        }
        $oClass   = new ReflectionClass('App\Interfaces\ResourceInterface');
        $constArs = $oClass->getConstants();
        foreach($constArs as $key => $val){
            if(strcasecmp($val, $checkKey) == 0){
                return true;
            }
        }
        return false;
    }

    /**
     * Internal request to other method
     *
     * @param $type
     * @param $method
     * @param array $params
     *
     * @return mixed
     */
    protected function internalRequest($type, $method, $params = [], $returnErrors = false)
    {
        $client        = app()->make('resource.' . $type);
        $requestResult = call_user_func_array([$client, $method], ['params' => $params, 'path' => $type . '/' . $method, 'validator' => $this->validator]);

        if($returnErrors && $this->resultHasError($requestResult)){
            return $requestResult;
        }

        return isset($requestResult['result']) ? $requestResult['result'] : [];
    }

    public function resultHasError($result, $multi = false)
    {
        if($multi){
            foreach($result as $res) {
                if (isset($res['error_messages']) || isset($res['error'])) {
                    return true;
                }
            }
            return false;
        }
        return isset($result['error_messages']) || isset($result['error']);
    }

    public function setErrorData(array $error)
    {
        if (isset($error['error_messages']))
            $this->errorMessages = $error['error_messages'];
        else if (isset($error['error']))
            $this->setErrorString($error['error']);
    }

    public function addErrorData(array $error)
    {
        if (isset($error['error_messages']))
            $this->errorMessages = array_merge($this->errorMessages, $error['error_messages']);
        else if (isset($error['error']))
            $this->setErrorString($this->getErrorString() == '' ? $error['error'] : $this->getErrorString() .' - '. $error['error']);
    }

    public function isDocumentRequest()
    {
        return $this->documentRequest;
    }

    public function isFunnelRequest()
    {
        return $this->funnelRequest;
    }

    /**
     * Defines wheter this resource requests should be used to populate the products
     * @return mixed
     */
    public function isPopulateRequest()
    {
        return $this->populateRequest;
    }

    /**
     * @return mixed for instance 'carinsurance'
     */
    public function getRequestType()
    {
        $explode = explode('/', $this->path);
        return $explode[0];
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function escapeBrackets($value)
    {
        $return = $value;
        $return = str_replace(")", "BRACKET_CLOSE", $return);
        $return = str_replace("(", "BRACKET_OPEN", $return);
        return $return;
    }


    protected function getSchemaContext($source)
    {

        $tags = [
            'document.index' => 'product',
            'document.type'  => $source,
        ];
        /** @var Registry $registry */
        $registry = App::make('Komparu\Schema\Contract\Registry');

        $schemas  = $registry->find($tags);
        $document = $registry->build(current($schemas));

        return $document->toArray();
    }

    protected function getUniqueKeys($schemaContext)
    {
        $uniquekeys = [];
        if(isset($schemaContext['document.unique'])){
            foreach($schemaContext['document.unique'] as $uniqueField){
                $uniquekeys[] = $uniqueField['name'];
            }
        }
        return $uniquekeys;
    }

    /**
     * Parse the error response of the webservice. This can be different for each service
     *
     * @param $error
     */
    protected function parseResponseError($error, \Exception $exception = null)
    {
        return $error;
    }


    /**
     * Remove duplicates based on schema
     *
     * @param array $result
     * @param $source
     *
     * @return array
     */
    protected function removeDuplicates(Array $result, $source)
    {
        $schemaContext = $this->getSchemaContext($source);
        $uniqueKeys    = $this->getUniqueKeys($schemaContext);
        $returnMap     = [];
        $duplicates    = 0;
        foreach($result as $resItem){
            $found = false;
            foreach($returnMap as $returnMapItem){
                $check = true;
                foreach($uniqueKeys as $uniqueKey){
                    if($returnMapItem[$uniqueKey] != $resItem[$uniqueKey]){
                        $check = false;
                        break;
                    }
                }
                if($check){
                    $found = true;
                    continue;
                }
            }
            if( ! $found){
                $returnMap[] = $resItem;
            }else{
                $duplicates ++;
            }
        }
        return $returnMap;
    }


    /**
     * Flatten and transform to underscore
     *
     * @param $res
     *
     * @return array
     */
    protected function flattenUnderscore($res)
    {
        $flattenRes = [];
        foreach(array_dot($res) as $fKey => $kValue){
            $flattenRes[strtolower(str_replace('.', '_', $fKey))] = $kValue;
        }
        return $flattenRes;
    }

    /**
     * @param $result
     *
     * @return array
     */
    protected function filterResultsOnDuplicates($result)
    {
        return array_map("unserialize", array_unique(array_map("serialize", $result)));
    }

}
