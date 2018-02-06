<?php
/**
 * Created by PhpStorm.
 * User: giorgi
 * Date: 11/22/17
 * Time: 10:29 AM
 */

namespace App\Resources\Moneyview2\Requests;

use App\Interfaces\ResourceInterface;
use App\Resources\MappedHttpMethodRequest;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use SimpleXMLElement;
use SoapClient;
use SoapVar;
use Spatie\ArrayToXml\ArrayToXml;

class BaseRequest extends MappedHttpMethodRequest
{
    const TASK_KEY = 'task';
    const TASK_PROCESS_ONE = 'PROCESS_ONE';
    const TASK_PROCESS_TWO = 'PROCESS_TWO';
    const TASK_LOOKUP = 'LOOKUP';
    const TASK_COVERAGE = 'COVERAGE';

    const FIELD_KEY = 'field';
    const MODULE_KEY = 'module';
    const API_KEY = 'berekening_my';
    const GLOBAL_KEY = 'global';
    const GLOBAL_ALL = 'all';
    const PAY_TERM_KEY = 'berekening_betalingstermijn';
    const PAY_TERM_YEAR = 'jaar';
    const PAY_TERM_MONTH = 'maand';
    const ASSUR_TAX_KEY = 'BEREKENING_ASSU_BELAST';
    const ASSUR_TAX = 'ja';
    const CALCULATION_FORM_KEY = 'Berekening_Vorm';
    const CALCULATION_FORM = 'eug+ar';
    const CONTENT_VALUE_ESTITMATION_KEY = 'INB_Waardebepaling';
    const CONTENT_VALUE_ESTITMATION = 'waardemeter';
    const HOME_VALUE_ESTITMATION_KEY = 'Ops_Waardebepaling';
    const HOME_VALUE_ESTITMATION = 'waardemeter';

    protected $soapClient = null;

    protected $clientParams = [
        'uid' => null,
        'task' => null,
        'global' => null,
        'local' => null,
        'specific' => null,
        'profile' => null,
    ];

    protected $stripAdvisorAndCollectiveResults = true;

    public function __construct()
    {
        app()->configure('resource_moneyview2');
        $this->soapClient = new SoapClient(
            $this->config('settings.wsdl' ),
            [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE, // We already have it on disk
            ]
        );

        $this->clientParams['uid'] = $this->config('settings.uid');
    }

    public function setParams(array $params)
    {
        $this->clientParams['profile'] = array_merge($params, $this->clientParams['profile']);
    }

    public function executeFunction()
    {
        $result = $this->startSoap();

        $this->result =  $this->transformResult($result);

        //Check for errors:
        $this->checkForError($this->result);
    }

    /**
     * Checks if the result has any errors; if it has, display it...
     * (This can probably be done a lot nicer... feel free)
     * @param $result
     * @return void
     */
    public function checkForError($result)
    {
        if($this->config('debug.checkForError'))
        {
            //Check if there is any `GLOBAL_ESCAPE_REASON` in first child as we would need that to check...
            if (is_array(head($result)) && array_key_exists('GLOBAL_ESCAPE_REASON', head($result)))
            {
                foreach ($result as $message)
                {
                    //Check that both messages are set and that the escape reason | local strings contains the word(s) under...
                    if (isset($message['GLOBAL_ESCAPE_REASON'])
                        && isset($message['LOCAL'])
                        && Str::contains($message['GLOBAL_ESCAPE_REASON'] . ' ' . $message['LOCAL'], ['GEVULD', 'NIET GEVULD', 'ERROR'])
                    ) {
                        $this->setErrorString($message['GLOBAL_ESCAPE_REASON'] . ' ' . $message['LOCAL']);
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function transformResult($result)
    {
        return $this->transformSoapResult($result, function($nodeEntry, &$products)
        {
            $subArr = [];

            foreach ($nodeEntry->children() as $subKey => $subNodeEntry) {
                $subArr[$subKey] = $subNodeEntry . "";
            }

            if($this->stripAdvisorAndCollectiveResults && $this->arrayHasAny($subArr, 'SPECIFIC', ['adviseur', 'collectief'])){
                return null;
            }

            $products[] = $subArr;

            return true;
        });
    }

    /**
     * @param $response
     * @param $function
     * @return array
     */
    public function transformSoapResult($response, $function)
    {
        if(!count($response)) {
            return [];
        }

        $xml = new SimpleXMLElement($response->StartSoapResult);

        $products = [];

        foreach ($xml->children() as $key => $nodeEntry)
        {
            if ($key != 'response') {
                continue;
            }

            $transformerResponse = $function($nodeEntry, $products);

            if(is_null($transformerResponse)) {
                continue;
            }
        }

        $products = $this->filterResultsOnDuplicates($products);

        return $products;
    }

    /**
     * @param $array
     * @param $key
     * @param $needles
     * @return bool
     */
    public function arrayHasAny($array, $key, $needles)
    {
        $matches = 0;

        foreach ($needles as $needle)
        {
            if(stripos(array_get($array, $key), $needle) !== false) {
                $matches++;
            }
        }

        return !!$matches;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function startSoap()
    {
        $xml = ArrayToXml::convert($this->clientParams, 'ScripletEngineRequestParameters');

        $soapVar = new SoapVar($xml, XSD_STRING);

        try {

            $response = $this->soapClient->StartSoap([
                'xmlIn' => $soapVar
            ]);

            if($this->config('debug.rawOutput')) {
                dd($response);
            }

            return $response;
        }

        catch(Exception $e) {

            if($this->config('debug.exception')) {
                throw $e;
            }

        }

        return [];
    }

    public function config($key)
    {
        return Config::get("resource_moneyview2.{$key}");
    }

    public function choicelist($key)
    {
        return Config::get("resource_moneyview2.choicelist.{$key}");
    }

    /**
     * @param $input
     * @param $item
     * @return string
     */
    public function generateTitle($input, $item)
    {
        return $item[ResourceInterface::COMPANY_NAME] . ' ' . $item[ResourceInterface::PRODUCT_SUMMARY];
    }

    /**
     * @param $input
     * @return array
     */
    public function transformPostCode($input)
    {
        $matched = preg_match('~[a-z]{2}~i', $input, $matches);

        return [
            'code' => substr(trim($input), 0, 4),
            'chars' => $matched ? $matches[0] : null,
        ];
    }

}