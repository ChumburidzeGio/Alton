<?php


namespace App\Resources\Rolls\Methods;

use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;
use Cache;
use Config;
use Log;
use SimpleXMLElement;
use SoapFault;


/**
 * abstract_soap_request (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 10-aug-2010
 * Time: 18:29:25
 */
class RollsAbstractSoapRequest extends AbstractMethodRequest
{
    //additional static data loaded from the config
    protected $staticAdditionals = [];

    protected $client;
    protected $functie;


    protected $xml;
    protected $classname;
    protected $forcerequest;
    protected $errors;
    protected $result;
    protected $test;
    protected $ci;
    protected $xmlpath;
    protected $officeId;


    protected $fuelTypeMapping = [
        1 => 'Benzine',
        2 => 'Diesel',
        3 => 'LPG',
        4 => 'Elektrisch',
        5 => 'CNG',
        6 => 'Alcohol',
        7 => 'Cryogeen',
        8 => 'Waterstof'
    ];

    protected $rollsCoverageMapping = [
        'NC'                 => ResourceInterface::NO_CLAIM_VALUE,
        'NOCG'               => ResourceInterface::NO_CLAIM_VALUE,
        'PHNL'               => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        'PH NL'              => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        'PH nederland'       => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        'PHBI'               => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        'PHN'                => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        'PHBN'               => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        'PHE'                => ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        'PHBU'               => ResourceInterface::ROADSIDE_ASSISTANCE_ABROAD_VALUE,
        'PHBT'               => ResourceInterface::ROADSIDE_ASSISTANCE_ABROAD_VALUE,
        'PH EU'              => ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE,
        'PHEU'               => ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE,
        'PH Europa 12 mnd'   => ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE,
        'PH Europa excl NL'  => ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE,
        'PHB'                => ResourceInterface::ROADSIDE_ASSISTANCE_ABROAD_VALUE,
        'PH'                 => ResourceInterface::ROADSIDE_ASSISTANCE_VALUE,
        'SVI'                => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
        'ASVI'               => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
        'SVI Basis'          => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
        'SVI eenpersoons'    => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
        'SVI meerpersoons'   => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
        'Schadeverzekering inzittenden' => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE, // 'Schadeverzekering inzittenden' (car product 273, KNMV)
        'RB'                 => ResourceInterface::LEGALEXPENSES_VALUE,
        'Rechtsbijstand'     => ResourceInterface::LEGALEXPENSES_VALUE,
        'RB Basis'           => ResourceInterface::LEGALEXPENSES_VALUE,
        'RB uitgebreid'      => ResourceInterface::LEGALEXPENSES_EXTENDED_VALUE,
        'RB excl. strafzaken' => ResourceInterface::LEGALEXPENSES_VALUE, // "Rechtsbijstand exclusief strafzaken" (car product 267, Klaverblad)
        'RB alleenstaande'   => ResourceInterface::LEGALEXPENSES_VALUE, // "Rechtsbijstandverzekering voor verkeersdeelnemers alleenstaande"
        'RB gezin'           => ResourceInterface::LEGALEXPENSES_VALUE, // "Rechtsbijstandverzekering voor verkeersdeelnemers gezin"
        'RB Uitgebreid'      => ResourceInterface::LEGALEXPENSES_EXTENDED_VALUE,
        'RB met CRB'         => ResourceInterface::LEGALEXPENSES_VALUE, // "Rechtsbijstand met contractsrechtsbijstand"
        'VRB'                => ResourceInterface::REDRESS_SERVICE_VALUE,
        ' VRB'               => ResourceInterface::REDRESS_SERVICE_VALUE, // "Verhaalshulp"
        'VHS'                => ResourceInterface::REDRESS_SERVICE_VALUE,
        'VSA'                => ResourceInterface::REDRESS_SERVICE_VALUE,
        'BB'                 => ResourceInterface::TIRES_BUNDLE_VALUE,
        'OVI-O'              => ResourceInterface::ACCIDENT_AND_DEATH_VALUE,
        'OVI-I'              => ResourceInterface::ACCIDENT_AND_DISABLED_VALUE,
        'OVI'                => ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE,
        'OVI Basis'          => ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE, // "Ongevallen inzittenden Basis personenauto", (car product 285)
        //'OVI Cumulatief'     => ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE, // "Ongevallen Inzittenden Cumulatief" (van product 41, 'Allianz Nederland Individueel')
        'OIV'                => ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE,
        'OI'                 => ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE,
        'SVB'                => ResourceInterface::DRIVER_INSURANCE_DAMAGE_VALUE,
        'Vervangend vervoer' => ResourceInterface::REPLACEMENT_VEHICLE_VALUE,
        'VVEU'               => ResourceInterface::REPLACEMENT_VEHICLE_VALUE, //"Vervangend Vervoer Verzekering Europa" (Car product 94, ANWB)
        'VV A'               => ResourceInterface::REPLACEMENT_VEHICLE_VALUE, //"Vervangend Vervoer Module A" (Car product 285, London)
        'VV B'               => ResourceInterface::REPLACEMENT_VEHICLE_VALUE, //"Vervangend Vervoer Module B" (Car product 285, London)
        'VV C'               => ResourceInterface::REPLACEMENT_VEHICLE_VALUE, //"Vervangend Vervoer Module C" (Car product 285, London)
        'VSV'                => ResourceInterface::DRIVER_INSURANCE_DAMAGE_VALUE, //"Verkeersschadeverzekering" (Car product 261, ING)
        //'ZVI'               => 'unknown', //"Zekerheid Voor Inzittenden" (Car product 94, ANWB) http://www.anwb.nl/verzekeringen/autoverzekering/inzittendenverzekering

        // Vaninsurance
        'PHEUA'              => ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE, // "Pechhulp Europa inclusief aanhanger"
        //'AANH'               => 'Aanhanger casco', // "Aanhanger casco" (Van product 107, Reaal)
        //'AFHER'              => 'moo', // "Afkoop Herstelservice" (Van product 17, asr Zakelijk)

        // Contentsinsurance
        'Lijfsieraden'  => ResourceInterface::COVERAGE_JEWELRY_VALUE, // 'Lijfsieraden'
        'sieraden'      =>  ResourceInterface::COVERAGE_JEWELRY_VALUE, // 'sieraden (maatwerk)'
        'SIER'          =>  ResourceInterface::COVERAGE_JEWELRY_VALUE, // 'Sieradenverzekering wereldwijd'
        'MOB'           =>  ResourceInterface::COVERAGE_MOBILE_ELECTRONICS_VALUE, // 'Mobiele elektronica'
        'Buiten de woningdekking' => ResourceInterface::COVERAGE_OUTDOORS_VALUE,  // 'Buiten de woningdekking'
        'BUITEN'        => ResourceInterface::COVERAGE_OUTDOORS_VALUE, // 'Buitenhuisdekking'
        'BTN'           => ResourceInterface::COVERAGE_OUTDOORS_VALUE, // 'Buitenhuisrisico'
        'HBL'           => ResourceInterface::COVERAGE_RENTAL_OR_APPARTMENT_OWNERSHIP_VALUE,  // 'Huurders- / appartement eigenarenbelang'
        'EBL'           => ResourceInterface::COVERAGE_HOUSEOWNERSHIP_VALUE, // 'Eigenarenbelang'
        'GLAS'          => ResourceInterface::COVERAGE_GLASS_VALUE, // 'Glasdekking'
        'BEROEP'        => ResourceInterface::COVERAGE_OCCUPATIONAL_EQUIPMENT_VALUE, // 'Beroepsgereedschappen'
        'AVC'           => ResourceInterface::COVERAGE_AUDIO_VISUAL_COMPUTER_VALUE, // 'Audio- visuele en computerapparatuur (AVC)'
        'Kostbaarheden' => ResourceInterface::COVERAGE_VALUABLES_VALUE, // 'Kostbaarheden'

        // Homeinsurance
        'SAN'           => ResourceInterface::COVERAGE_DECONTAMINATION_VALUE, // 'Saneringskosten'
        'TUIN'          => ResourceInterface::COVERAGE_GARDEN_VALUE, // 'Tuin'
        'AANV'          => false, // '10% aanvullende dekking',
        'GLAS (OPS.)'   => ResourceInterface::COVERAGE_GLASS_VALUE, // 'Glasdekking'
        'Glas'          => ResourceInterface::COVERAGE_GLASS_VALUE, // 'Glas'
        'ONH'           => ResourceInterface::COVERAGE_DISASTER_VALUE, // 'Van buiten komend onheil'
    ];

    protected $selectedCoverages = [];


    public function __construct($type = 'webservice')
    {
        $this->forcerequest   = false;
        $this->rollSoapClient = new RollsSoapClient($type);
        $this->forcerequest   = ! (((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.soap_caching_enabled')));
        $this->test           = (((app()->configure('app')) ? '' : config('app.debug')) || config('app.TEST_MODE')) ? ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_ja')) : ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_nee'));
    }

    public function init($function)
    {
        $this->functie = $function;
        $this->getFunctieXMLTemplate();
    }

    public function dumpFunctions()
    {
        echo '<pre>';
        var_dump($this->rollSoapClient->__getFunctions());
        echo '</pre>';
    }

    protected function getFunctieXMLTemplate()
    {
        if($this->forcerequest){
            Log::debug("Force request on");
            $this->xml = $this->requestXmlTemplate();
            return;
        }

        $xmlfilestring = $this->xmlpath . $this->functie . ".xml";

        $this->xml = simplexml_load_string(Cache::tags('webservice', 'webservice.rolls')->rememberForever($xmlfilestring, function () {
            return $this->requestXmlTemplate()->asXML();
        }));
    }

    public function getXml()
    {
        return $this->xml->asXml();
    }

    public function executeFunction()
    {
        cws('start_rolls_execute');
        $this->staticAdditionals = config('resource_rolls.static.' . $this->getRequestType());

        /**
         * Check if this user has an external office, if so link it.
         */

        $officeId = ($this->officeId) ? $this->officeId: ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.rolls_kantoorid'));

        //echo $this->xml->asXml(); exit();
        $fu_param = array(
            'FunctieXML'        => $this->xml->asXml(),
            'ClientAppKey'      => ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.rolls_clientappkey')),
            'ClientAppPassword' => ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.rolls_clientpassword')),
            'RollsProductID'    => null,
            'KantoorID'         => $officeId,
            'Test'              => $this->test
        );

        //enabled this for debug
        cw($this->xml->asXml());
        if( ! isset($this->rollSoapClient) || ! $this->rollSoapClient instanceof RollsSoapClient){
            Log::debug('rolls_soap_client als fallback ingeladen');
            $this->rollSoapClient = new RollsSoapClient();
        }
        cws('start_rolls_execute_functie');
        $xmlresp = $this->rollSoapClient->ExecuteFunctie($fu_param);

        cwe('start_rolls_execute_functie');
        $xml = new SimpleXMLElement($xmlresp->ExecuteFunctieResult->any);

        cw($xml->saveXML());
        $this->result = $xml->Functie->Parameters;
        cw($this->result);
        $this->errors = $xml->Footer->Errors ?: $xml->Errors;
        if (isset($xml->Error))
            $this->setErrorString('Rolls general error: `'. $xml->Error .'`');
        if($this->isError()){
            $this->logErrors();

            if ($this->debug()) {
                $this->setErrorString('Rolls input errors: '. $this->errors->asXML());
            }
        }

        cwe('start_rolls_execute');
        return $xml;
    }


    /**
     * Retrieve XML file from Rolls webservice
     * @return int|SimpleXMLElement
     */
    private function requestXmlTemplate()
    {


        $ap_param = array(
            'FunctieKey'        => $this->functie,
            'ClientAppKey'      => ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.rolls_clientappkey')),
            'ClientAppPassword' => ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.rolls_clientpassword')),
            'RollsProductID'    => null,
            'KantoorID'         => ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.rolls_kantoorid')),
            'Test'              => 1
        );

        try{
            $this->client = new RollsSoapClient(((app()->configure('resource_rolls')) ? '' : config('resource_rolls.settings.rolls_functiewsdl_url')));

            $xmltemplate = $this->client->__soapCall('GetFunctieXmlTemplate', array($ap_param));
            $xml         = new SimpleXMLElement($xmltemplate->GetFunctieXmlTemplateResult->any);
            //$this->logXMLFile( $xml );

            //Log::debug(("REQUEST: " .  $this->client->__getLastRequest());
            //Log::debug(("RESPONSE: " . $this->client->__getLastResponse());
            return $xml;
        }catch(SoapFault $fault){
            Log::error("SOAP Request not succeeded: " . $fault->faultcode . "-" . $fault->faultstring . "");
            return 0;
        }
    }

    public function isError()
    {
        if( ! $this->errors){
            return false;
        }

        return sizeof($this->errors->children());
    }

    public function getErrors()
    {
        return $this->errors;
    }


    public function getErrorString()
    {
        if($this->customError){
            return $this->customError;
        }
        $s = "";
        if( ! $this->errors){
            return $s;
        }
        foreach($this->errors->children() as $a1 => $b1){
            $s .= $b1->Omschrijving . "<br/>";
        }
        return $s;
    }

    public function LogErrors()
    {
        foreach($this->errors->children() as $a1 => $b1){
            Log::debug(json_decode(json_encode($b1->Omschrijving, JSON_NUMERIC_CHECK), true));
        }
    }

    /**
     * @return SimpleXMLElement
     */
    public function getResult()
    {
        return $this->result;
    }

    public function getResultAsXml()
    {
        return $this->result->asXML();
    }

    function __destruct()
    {
        // destroy the client!
        unset($this->rollSoapClient);
    }

    function deleteParameterTree($name)
    {
        $params = $this->xml->Functie->Parameters->children();
        if($params){
            foreach($params as $naam => $node){
                if(((String) trim($naam)) == ((String) trim($name))){
                    $dom = dom_import_simplexml($node);
                    $dom->parentNode->removeChild($dom);
                    //Log::debug(('Node unset ' . $name.'='.$naam);
                    return;
                }
            }
        }
    }

    function getFunctionXml()
    {
        return $this->xml;
    }

    protected function extractResult($plural, $single, $result = null)
    {
        if( ! $result){
            $result = self::getResult(); // get the fucking parent!!
        }
        $retArray = [];
        if($plural != ''){
            $node = $result->{$plural};
        }else{
            $node = $result;
        }
        if(is_object($node->{$single})){
            foreach($node->children() as $nodeEntry){
                $retArray[] = $nodeEntry;
            }
        }
        foreach($retArray as &$row){
            if(property_exists($row,'Id')){
                $row->name  = $row->Id;
            }
            if(property_exists($row,'Naam')){
                $row->label = $row->Naam;
            }
        }
        return $retArray;
    }

    /**
     * Product selection
     */

    public function addPremieobjecteProductselectieArray($productarray)
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product);
        foreach($productarray as $prodid){
            $product = $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->addChild('PO_Product');
            $product->addChild('Id', $prodid);
        }
    }

    public function deletePremieobjectenProductselectie()
    {
        $node = $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie;
        if($node){
            $dom = dom_import_simplexml($node);
            $dom->parentNode->removeChild($dom);
        }
    }

    /**
     * Tree/subtrees
     */

    protected function deleteParameterSubTree($node, $name)
    {
        $params = $this->xml->Functie->Parameters->{$node}->children();
        if($params){
            foreach($params as $naam => $node){
                if(((String) trim($naam)) == ((String) trim($name))){
                    $dom = dom_import_simplexml($node);
                    $dom->parentNode->removeChild($dom);
                    //Log::debug(('Node unset ' . $name.'='.$naam);
                    return;
                }
            }
        }
    }

    protected function deleteParameterSubSubTree($node, $subnode, $name)
    {
        $params = $this->xml->Functie->Parameters->{$node}->{$subnode}->children();
        if($params){
            foreach($params as $naam => $node){
                if(((String) trim($naam)) == ((String) trim($name))){
                    $dom = dom_import_simplexml($node);
                    $dom->parentNode->removeChild($dom);
                    //Log::debug(('Node unset ' . $name.'='.$naam);
                    return;
                }
            }
        }
    }

    public function deleteAanvullingen()
    {
        unset($this->xml->Functie->Parameters->Polis->Aanvullingen);
    }

    protected function processAdditionalCoverages($result)
    {
        $productToResultKey = [];

        $result = json_decode(json_encode($result, JSON_NUMERIC_CHECK), true);

        $productArray = [];
        $key = null;
        foreach($result as $resultItem)
        {
            if (!isset($resultItem['Combinatie']['Productpremies']['Productpremie'])) {
                // This is probably a product with 'Acceptatie' false, we ignore them (for now)
                // TODO: Register these acceptation errors somewhere to use/return
                continue;
            }

            if (isset($productToResultKey[$resultItem['Productnaam']]))
            {
                // Found a product we have seen before
                $key = $productToResultKey[$resultItem['Productnaam']];
            }
            else
            {
                // Found a new product
                $key = count($productArray);
                $productArray[$key] = $resultItem;
                $productArray[$key][ResourceInterface::COVERAGES] = [];
                $productArray[$key][ResourceInterface::PRICE_COVERAGES] = 0;
                unset($productArray[$key]['Combinatie']); // Unset for clarity in result
                unset($productArray[$key]['PremiebedragInCenten']);
                unset($productArray[$key]['Premiebedragencenten']);
                $productToResultKey[$resultItem['Productnaam']] = $key;
            }

            foreach ($resultItem['Combinatie']['Productpremies']['Productpremie'] as $premiumType) {

                //TODO: handle 'Termijntoeslagpercentage'

                if ($premiumType['Productnaamtoshow'] == $resultItem['Productnaam'] || $premiumType['Productnaam'] == $resultItem['Productnaam']) {
                    //TODO: Remove hardcoded 21% tax
                    // Recalculate basic product cost (gets divided by 100 later)
                    $productArray[$key]['PremiebedragInCenten'] = ($premiumType['Nettojaarpremie'] * 1.21) / 12;
                } else if (isset($this->rollsCoverageMapping[$premiumType['Productnaamtoshow']])) {

                    // Known additional coverage we do not want to show?
                    if ($this->rollsCoverageMapping[$premiumType['Productnaamtoshow']] === false)
                        continue;

                    $price = ($premiumType['Nettojaarpremie'] * 1.21) / 12 / 100;

                    // Add additional coverage as a field
                    $productArray[$key][$this->rollsCoverageMapping[$premiumType['Productnaamtoshow']]] = $price;

                    // Add additional coverage in the 'coverages' array
                    $coverageTypeName = preg_replace(['~^coverage_~', '~_value$~'], '', $this->rollsCoverageMapping[$premiumType['Productnaamtoshow']]);
                    $coverage = [
                        'name' => $coverageTypeName,
                        'title' => $premiumType['Productnaam'],
                        'resource.id' => $premiumType['Productid'],
                        'price' => $price,
                        'is_selected' => in_array($coverageTypeName, $this->selectedCoverages),
                        'is_covered' => in_array($coverageTypeName, $this->selectedCoverages) || $price === 0.0,
                        'is_available' => true,
                    ];
                    $productArray[$key][ResourceInterface::COVERAGES][] = $coverage;


                    // Total all our selected coverage prices
                    if ($coverage['is_selected'])
                        $productArray[$key][ResourceInterface::PRICE_COVERAGES] += $price;
                }
                else {
                    Log::info('Unknown additional coverage `'. $premiumType['Productnaamtoshow'] .'` in '. get_class($this) .' - '. json_encode($premiumType));
                }
            }
        }

        foreach ($productArray as $key => $product) {
            $selectedCoveragesFound = array_filter($product[ResourceInterface::COVERAGES], function ($value) { return $value['is_selected']; });
            if (count($selectedCoveragesFound) != count($this->selectedCoverages))
                unset($productArray[$key]);
        }

        return array_values($productArray);
    }

    protected function getMotorizedPremieResult($optionlList, $extended = false, $advice = false)
    {
        //call license client
        cws('carinsurance_internal_list');
        $listRes = $this->internalRequest('carinsurance', 'list', [ResourceInterface::OPTIONLIST => $optionlList]);
        cwe('carinsurance_internal_list');
        $ratingMaping = isset($listRes['ratings']) ? $listRes['ratings'] : [];

        /** @var SimpleXMLElement $premObjs */
        $result       = self::getResult();
        $premObjArray = $result->Premieobjecten->Premieobject;


        $returnArr = [];
        foreach($premObjArray as $premObjs){

            $result = $this->extractResult('Premies', 'Premie', $premObjs);
            $result = json_decode(json_encode($result, JSON_NUMERIC_CHECK), true);
            foreach($result as $key => $val){
                foreach($val as $valkey => $valval){
                    if(is_array($valval) && count($valval) == 0){
                        $val[$valkey] = 0;
                    }
                }

                /**
                 * Handle acceptation
                 */
                $val[ResourceInterface::SECURITY_ERROR]   = false;
                $val[ResourceInterface::SECURITY_MINIMAL] = 0;
                if($val['Acceptatie'] == 'nee'){
                    if($extended && is_array($val['Acceptatiemeldingen']['Acceptatiemelding']) && count($val['Acceptatiemeldingen']['Acceptatiemelding']) > 2){
                        //serious multiple acc situation
                        if(($val['Productnaam'] == 'Lancyr autotarief' || $val['Productid'] == 7961) && ! $this->hasErrors()){
                            foreach($val['Acceptatiemeldingen']['Acceptatiemelding'] as $melding){
                                if( ! isset($melding['Foutnummer'])){
                                    continue;
                                }
                                if($melding['Foutnummer'] == 36){
                                    continue;
                                }
                                $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'carinsurance.rolls.error.' . $melding['Foutnummer'], $melding['Omschrijving'], 'input');
                            }
                        }

                        continue;

                    }else{

                        // check of er maar 1 melding is
                        if( ! isset($val['Acceptatiemeldingen']['Acceptatiemelding'])){
                            if($extended && ($val['Productnaam'] == 'Lancyr autotarief' || $val['Productid'] == 7961) && ! $this->hasErrors()){
                                $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'carinsurance.rolls.error.global', $val['Acceptatiemeldingen'], 'input');
                            }
                            continue;
                        }
                        if( ! isset($val['Acceptatiemeldingen']['Acceptatiemelding']['Foutnummer']) || ($val['Acceptatiemeldingen']['Acceptatiemelding']['Foutnummer'] != 36)){
                            if($extended && ($val['Productnaam'] == 'Lancyr autotarief' || $val['Productid'] == 7961) && ! $this->hasErrors()){
                                /**
                                 * seriously rolls??
                                 */
                                if(isset($val['Acceptatiemeldingen']['Acceptatiemelding'][0])){
                                    foreach($val['Acceptatiemeldingen']['Acceptatiemelding'] as $keyacc => $valueacc){
                                        $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'carinsurance.rolls.error.' . $val['Acceptatiemeldingen']['Acceptatiemelding'][0]['Foutnummer'],
                                            $val['Acceptatiemeldingen']['Acceptatiemelding'][$keyacc]['Omschrijving'], 'input');
                                    }

                                }else{
                                    $this->addErrorMessage(ResourceInterface::LICENSEPLATE,
                                        'carinsurance.rolls.error.' . (isset($val['Acceptatiemeldingen']['Acceptatiemelding']['Foutnummer']) ? $val['Acceptatiemeldingen']['Acceptatiemelding']['Foutnummer'] : 'onbekend'),
                                        $val['Acceptatiemeldingen']['Acceptatiemelding']['Omschrijving'], 'input');
                                }
                            }
                            if (!$this->debug())
                                continue;
                        }

                        // Set rejection messages
                        if (isset($val['Acceptatiemeldingen']['Acceptatiemelding'])) {
                            $val[ResourceInterface::REJECTION_MESSAGE] = $val['Acceptatiemeldingen']['Acceptatiemelding']['Omschrijving'];
                        }

                        // Try to detect minimal security error
                        preg_match('/is (\d)./', $val['Acceptatiemeldingen']['Acceptatiemelding']['Omschrijving'], $matches);
                        if( isset($matches[1])){
                            $val[ResourceInterface::SECURITY_ERROR] = true;
                            $val[ResourceInterface::SECURITY_MINIMAL] = $matches[1];
                        }
                        else {
                            if ($this->debug()) {
                                // Set price to 0 to make sure we don't actually return results
                                $val[ResourceInterface::PRICE_DEFAULT] = 0;
                            }
                            else {
                                continue;
                            }
                        }
                    }
                }
                $assurantieBelasting = 1 + array_get($val, 'Assurantiebelastingpercentage', 0) / 10000;

                //poliskosten zijn zondere assurantie belasting
                $val['PoliskostenInCenten'] = $assurantieBelasting * $val['PoliskostenInCenten'];

                if( ! isset($val['Eigenrisico'])){
                    $val['Eigenrisico'] = 0;
                };


                if(isset($val['PR_Rubrieken'])){
                    $ratings = [];
                    /**
                     * Gebruik dit voor rating berekening
                     */
                    $ratingList = [6, 164, 3, 196, 180, 70, 75, 226, 158, 148, 53, 43];
                    if($val['Eigenrisico'] > 0){
                        $ratingList[] = 105;
                    }

                    $ratingValue = 0;
                    $ratingCount = 0;
                    foreach($val['PR_Rubrieken']['PR_Rubriek'] as $rating){
                        $rating[ResourceInterface::NAME] = $ratingMaping[$rating['Id']][ResourceInterface::NAME];
                        //echo $rating[ResourceInterface::NAME].PHP_EOL;
                        $ratings[] = $rating;
                        if(in_array($rating['Id'], $ratingList)){
                            $ratingValue += $rating['Score'];
                            $ratingCount ++;
                        }
                    }
                    unset($val['PR_Rubrieken']);
                    $val[ResourceInterface::RATINGS]        = $ratings;
                    $val[ResourceInterface::AVERAGE_RATING] = $ratingCount > 0 ? $ratingValue / $ratingCount / 1000 : 0;
                }
                $val[ResourceInterface::COVERAGE]                          = '' . $premObjs->Dekking;
                $val[ResourceInterface::ACCESSOIRES_COVERAGE_SINGLE_VALUE] = $premObjs->Premieaccessoiredekkingincenten + 0;
                $val[ResourceInterface::ADVISE]                            = ($advice == $val[ResourceInterface::COVERAGE]);
                $returnArr[]                                               = $val;
            }
        }

        if( ! count($returnArr)){
            // Errors may be set, but only for Lancyr
            return [];
        }else{
            $this->clearErrors();
        }
        //process extended hell
        if( ! $extended){
            return $returnArr;
        }
        $foundCoverages = [];
        $counter        = - 1;

        $printCoverages = [];

        $strippedArr = [];
        foreach($returnArr as $coverageEntry){
            if( ! in_array($coverageEntry[ResourceInterface::COVERAGE] .'|'. $coverageEntry['Eigenrisico'], $foundCoverages)){
                $counter ++;
                $foundCoverages[]                              = $coverageEntry[ResourceInterface::COVERAGE] .'|'. $coverageEntry['Eigenrisico'];
                $strippedArr[$counter]                         = $coverageEntry;
                $strippedArr[$counter]['PremiebedragInCenten'] = isset($strippedArr[$counter]['Combinatie']['Productpremies']['Productpremie'][0]['Termijnpremie']) ? $strippedArr[$counter]['Combinatie']['Productpremies']['Productpremie'][0]['Termijnpremie'] : 0.0;
            }

            if( ! isset($coverageEntry['Combinatie']['Productpremies']['Productpremie'][1])){
                continue;
            }
            $coverage = $coverageEntry['Combinatie']['Productpremies']['Productpremie'][1];
            if( ! isset($this->rollsCoverageMapping[$coverage['Productnaamtoshow']])){
                Log::info('No coverage mapping for ' . $coverage['Productnaamtoshow']);
                Log::info($coverage);
                continue;
            }
            $printCoverages[]                                                                   = $coverage;
            $strippedArr[$counter][$this->rollsCoverageMapping[$coverage['Productnaamtoshow']]] = round(($coverage['Nettojaarpremie'] / 1200), 8);
        }
        return $strippedArr;
    }


    protected function getPolisResult()
    {
        $returnArr = [];
        $res       = $this->processResult(json_decode(json_encode($this->extractResult('Rubrieken', 'Rubriek', self::getResult()->Polisuittreksel), JSON_NUMERIC_CHECK), true));
        foreach($res as $conditions){
            $lines = [];
            //plus en min munten speciale situatie
            if($conditions[ResourceInterface::TITLE] == 'Plus- en minpunten'){
                if(isset($conditions['rows']['row'][0]['cols']['col'][ResourceInterface::TEXT])){
                    $lines[$conditions['rows']['row'][0]['cols']['col'][ResourceInterface::TEXT]] = $this->createHtmlList($conditions['rows']['row'][1]['cols']['col'][ResourceInterface::TEXT]);
                }
                if(isset($conditions['rows']['row'][2]['cols']['col'][ResourceInterface::TEXT])){
                    $lines[$conditions['rows']['row'][2]['cols']['col'][ResourceInterface::TEXT]] = $this->createHtmlList($conditions['rows']['row'][3]['cols']['col'][ResourceInterface::TEXT]);
                }
            }else{
                if(isset($conditions['rows']) && isset($conditions['rows']['row'])){
                    foreach($conditions['rows']['row'] as $row){
                        if(isset($row['cols']['col'][0][ResourceInterface::TEXT])){
                            $lines[$row['cols']['col'][0][ResourceInterface::TEXT]] = $row['cols']['col'][1][ResourceInterface::TEXT];
                        }
                    }
                }
            }
            $returnArr[$conditions[ResourceInterface::TITLE]] = $lines;
        }
        return $returnArr;
    }

    private function createHtmlList($string)
    {
        if(strpos($string, '-') === false){
            return trim($string);
        }
        preg_match_all('/- (.+)/', $string, $matches);
        $arr       = $matches[1];
        $returnstr = '<ul>' . PHP_EOL;
        foreach($arr as $row){
            $returnstr .= "<li>" . $row . "</li>" . PHP_EOL;
        }
        $returnstr .= '</ul>' . PHP_EOL;
        return $returnstr;
    }


    public function logXMLFile(SimpleXMLElement $simpleXml)
    {
        $dom                     = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput       = true;
        $dom->loadXML($simpleXml->asXML());
        echo $dom->saveXML();
        exit;
    }


    function rollsBool($bool)
    {
        if(is_numeric($bool)){
            return $bool && $bool == 1 ? ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_ja')) : ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_nee'));
        }
        return strtolower($bool) == 'true' ? ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_ja')) : ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_nee'));
    }


    /**
     * Dekking advies op basis van constructiondate
     * //    � auto's jonger dan 6 jaar: WA + Volledig Casco
     * //    � auto's tussen de 6 en 8 jaar oud: WA + Beperkt Casco
     * //    � auto's ouder dan 8 jaar
     *
     * @param $constructionDate
     *
     * @return string
     */
    protected function getDekkingAdvies($constructionDate)
    {
        $date  = strtotime($constructionDate);
        $diff  = abs(time() - $date);
        $years = floor($diff / (365 * 60 * 60 * 24));
        if($years < 6){
            return 'vc';
        }
        if($years <= 8){
            return 'bc';
        }
        return 'wa';
    }


    //helpers
    function getAge($birthdate)
    {
        $dob   = strtotime(str_replace("/", "-", $birthdate));
        $tdate = time();

        $age = 0;
        while($tdate > $dob = strtotime('+1 year', $dob)){
            ++ $age;
        }
        return $age;
    }

    function getJarenRijwewijs($leeftijdrijbewijs, $geboortedatum)
    {
        //In:yyyymmdd
        $age            = $this->getAge($geboortedatum);
        $jarenrijbewijs = ($age - $leeftijdrijbewijs);
        if($jarenrijbewijs < 0){
            return 0;
        }
        return $jarenrijbewijs;
    }

    public function addProductIdsFilter($productIds)
    {
        if (!is_array($productIds) && is_string($productIds))
            $productIds = explode(',', $productIds);

        if (!isset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie))
            $this->xml->Functie->Parameters->Premieobjecten->Premieobject->addChild('PO_Productselectie');

        // Remove any 'example' PO_Product we got from templates
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product);

        // Add product id filters
        foreach ($productIds as $productId){
            $product = $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->addChild('PO_Product');
            $product->addChild('Id', $productId);
        }
    }
}
