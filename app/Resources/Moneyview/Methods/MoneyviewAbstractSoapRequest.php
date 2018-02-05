<?php
/**
 * User: Roeland Werring
 * Date: 10/03/15
 * Time: 15:40
 *
 * godverdomme kanker ding
 *
 */


namespace App\Resources\Moneyview\Methods;

use App\Resources\AbstractMethodRequest;
use Config;
use Exception;
use SimpleXMLElement;
use SoapVar;


/**
 * abstract_soap_request (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 10-aug-2010
 * Time: 18:29:25
 */
class MoneyviewAbstractSoapRequest extends AbstractMethodRequest
{
    const TASK_KEY = 'task';
    const TASK_PROCESS_ONE = 'PROCESS_ONE';
    const TASK_PROCESS_TWO = 'PROCESS_TWO';
    const TASK_LOOKUP = 'LOOKUP';
    const TASK_COVERAGE = 'COVERAGE';

    const FIELD_KEY = 'field';
    const MODULE_KEY = 'module';
    const BEREKENING_MY_KEY = 'berekening_my';
    const GLOBAL_KEY = 'global';
    const GLOBAL_ALL = 'all';
    const PAY_TERM_KEY = 'berekening_betalingstermijn';
    const PAY_TERM = 'jaar';
    const PAY_TERM_MONTH = 'maand';
    const ASSUR_TAX_KEY = 'BEREKENING_ASSU_BELAST';
    const ASSUR_TAX = 'ja';
    const CALCULATION_FORM_KEY = 'Berekening_Vorm';
    const CALCULATION_FORM = 'eug+ar';
    const CONTENT_VALUE_ESTITMATION_KEY = 'INB_Waardebepaling';
    const CONTENT_VALUE_ESTITMATION = 'waardemeter';
    const HOME_VALUE_ESTITMATION_KEY = 'Ops_Waardebepaling';
    const HOME_VALUE_ESTITMATION = 'waardemeter';

    protected $defaultParams = [];
    protected $result;

    protected $stripAdvisorAndCollectiveResults = true;

    /**
     * @var \SoapClient $soapClient
     */
    protected $soapClient;

    protected $xmlTemplate = <<<XML
<?xml version="1.0"?>
<ScripletEngineRequestParameters>
    <uid>%UID%</uid>
    <task>%TASK%</task>
    <global>%GLOBAL%</global>
    <local>%LOCAL%</local>
    <specific>%SPECIFIC%</specific>
    <profile>

        %PARAMS%

    </profile>
</ScripletEngineRequestParameters>
XML;

    public function __construct($global, $task = '', $local = '', $specific = '')
    {
        $this->fillVar('GLOBAL', $global);
        $this->fillVar('TASK', $task);
        $this->fillVar('LOCAL', $local);
        $this->fillVar('SPECIFIC', $specific);
    }

    public function executeFunction()
    {
        $this->soapClient = new MoneyviewSoapClient();
        $this->fillVar('UID', ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.uid')));

        //dirty fix to undo boolean fuckup
        $this->xmlTemplate = str_replace('false', '0', $this->xmlTemplate);
        cw($this->xmlTemplate);
        $soapvar           = new SoapVar($this->xmlTemplate, XSD_STRING);

        //echo($this->xmlTemplate);die();
        $soapParams = array("xmlIn" => $soapvar);

        //call to webservice
        try{
            $this->result = $this->soapClient->StartSoap($soapParams);
        }catch(Exception $e){
            $this->result = [];
        }
    }

    public function getResult()
    {
        //echo($this->result->StartSoapResult);die();
        $xml      = new SimpleXMLElement($this->result->StartSoapResult);
        $retArray = [];
        //dd($xml->asXML() . $this->xmlTemplate);
        /** @var SimpleXMLElement $nodeEntry */
        foreach($xml->children() as $key => $nodeEntry){
            if($key != 'response'){
                continue;
            }
            $subArr = [];
            foreach($nodeEntry->children() as $subKey => $subNodeEntry){
                $subArr[$subKey] = $subNodeEntry . "";
            }
            //filter adviseur collectief
            if($this->stripAdvisorAndCollectiveResults && isset($subArr['SPECIFIC']) && ((stripos($subArr['SPECIFIC'], 'adviseur') !== false) || (stripos($subArr['SPECIFIC'], 'collectief') !== false))){
                continue;
            }
            $retArray[] = $subArr;
        }
        //remove doubles from result
        //remove doubles
        $retArray = $this->filterResultsOnDuplicates($retArray);
        return $retArray;
    }

    public function setParams(Array $params)
    {
        $allParams = array_merge($params, $this->defaultParams);
        $paramXml  = '';
        foreach($allParams as $key => $val){
            $paramXml .= $this->xmlField($key, $val);
        }
        $this->fillVar('PARAMS', $paramXml, true);
    }


    protected function fillVar($name, $value = '', $rawXml = false)
    {
        $this->xmlTemplate = str_ireplace('%' . $name . '%', (!$rawXml ? htmlspecialchars($value, ENT_NOQUOTES) : $value), $this->xmlTemplate);
    }


    /**
     * @param $key
     * @param $val
     *
     * @return string
     */
    protected function xmlField($key, $val, $rawXml = false)
    {
        return '<' . strtolower($key) . '>' . (!$rawXml ? htmlspecialchars($val, ENT_NOQUOTES) : $val) . '</' . strtolower($key) . '>' . PHP_EOL;
    }


    /**
     * @return array
     */
    protected function getPolicyResult()
    {
        if( ! isset($this->result->StartSoapResult)){
            return [];
        }
        $xml      = new SimpleXMLElement($this->result->StartSoapResult);
        $retArray = [];
        /** @var SimpleXMLElement $nodeEntry */
        foreach($xml->children() as $key => $nodeEntry){
            if($key != 'response'){
                continue;
            }
            /** @var SimpleXMLElement $subNodeEntry */
            foreach($nodeEntry->children() as $subKey => $subNodeEntry){
                if($subKey != 'CAPTION'){
                    continue;
                }
                $attr                                    = $subNodeEntry->attributes();
                $groupName                               = (String) $attr->group_name;
                $fieldDescription                        = (String) $attr->field_description;
                $retArray[$groupName][$fieldDescription] = $subNodeEntry . "";
            }
        }
        return $retArray;
    }
}
