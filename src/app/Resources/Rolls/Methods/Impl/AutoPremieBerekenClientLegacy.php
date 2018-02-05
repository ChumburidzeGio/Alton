<?php
/**
 * @deprecated
 *
 * (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 17-aug-2010
 * Time: 0:19:25
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;

class AutoPremieBerekenClientLegacy extends RollsAbstractSoapRequest
{

    protected $cacheDays = 1;

    protected $extended = false;

    private $advice;

    protected $outputFields = [
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_ACTUAL,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::OWN_RISK,
        ResourceInterface::TOTAL_RATING,
        ResourceInterface::RATINGS,
        ResourceInterface::COVERAGE,
        ResourceInterface::PRICE_FEE,

        //boolean
        ResourceInterface::DAMAGE_TO_OTHERS,
        ResourceInterface::THEFT,
        ResourceInterface::FIRE_AND_STORM,
        ResourceInterface::WINDOW_DAMAGE,
        ResourceInterface::VANDALISM,
        ResourceInterface::OWN_FAULT,

        ResourceInterface::ACCESSOIRES_COVERAGE,

        //extra coverage prices
        ResourceInterface::NO_CLAIM_VALUE,
        ResourceInterface::DRIVER_INSURANCE_DAMAGE_VALUE,
        ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
        ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE,
        ResourceInterface::LEGALEXPENSES_VALUE,
        ResourceInterface::LEGALEXPENSES_EXTENDED_VALUE,
        ResourceInterface::ACCESSOIRES_COVERAGE_VALUE,
        ResourceInterface::ROADSIDE_ASSISTANCE_VALUE,
        ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,
        ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE,
        ResourceInterface::ROADSIDE_ASSISTANCE_ABROAD_VALUE,
        ResourceInterface::ACCIDENT_AND_DEATH_VALUE,
        ResourceInterface::ACCIDENT_AND_DISABLED_VALUE,
        ResourceInterface::TIRES_BUNDLE_VALUE,
        ResourceInterface::REPLACEMENT_VEHICLE_VALUE,
        ResourceInterface::REDRESS_SERVICE_VALUE,
        ResourceInterface::ACCESSOIRES_COVERAGE_SINGLE_VALUE,

    ];

    protected $arguments = [
        ResourceInterface::BIRTHDATE                    => [
            'rules'   => self::VALIDATION_REQUIRED_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::DRIVERS_LICENSE_AGE          => [
            'rules'   => 'required | integer',
            'example' => '19',
        ],
        ResourceInterface::COVERAGE                     => [
            'rules'   => 'required',
            'example' => 'bc, vc or wa. Use all for all premiums',
        ],
        ResourceInterface::OWN_RISK                     => [
            'rules'   => 'in:0,150,300,999',
            'example' => '0,150,300,999',
        ],
        ResourceInterface::MILEAGE                      => [
            'rules'   => 'required | in:7500,10000,12000,15000,20000,25000,30000,90000',
            'example' => '7500,10000,12000,15000,20000,25000,30000,90000',
        ],
        ResourceInterface::YEARS_WITHOUT_DAMAGE         => [
            'rules'   => 'required | number',
            'example' => '10',
        ],
        ResourceInterface::POSTAL_CODE                  => [
            'rules'   => self::VALIDATION_REQUIRED_POSTAL_CODE,
            'example' => '8014EH',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::TYPE_ID                      => [
            'rules'   => 'number',
            'example' => '84654',
        ],
        ResourceInterface::LICENSEPLATE                 => [
            'rules'   => self::VALIDATION_LICENSEPLATE,
            'example' => '35-JDR-8',
            'filter'  => 'filterAlfaNumber'
        ],
        ResourceInterface::CONSTRUCTION_DATE            => [
            'rules'   => 'string',
            'example' => '2009-04-01',
        ],
        ResourceInterface::CONSTRUCTION_DATE_MONTH => [
            'rules'   => 'number',
            'example' => '03',
        ],
        ResourceInterface::CONSTRUCTION_DATE_YEAR => [
            'rules'   => 'number',
            'example' => '2012',
        ],
        ResourceInterface::HOUSE_NUMBER                 => [
            'rules'   => 'integer',
            'example' => '21'
        ],
        ResourceInterface::IDS                          => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
            'default' => []
        ],
        ResourceInterface::RESOURCE_ID => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
            'default' => []
        ],
        ResourceInterface::NO_CLAIM                     => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],
        ResourceInterface::LEGALEXPENSES                => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],
        ResourceInterface::PASSENGER_INSURANCE_DAMAGE   => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],
        ResourceInterface::PASSENGER_INSURANCE_ACCIDENT => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],
        ResourceInterface::INCLUDE_VAT               => [
            'rules'   => self::VALIDATION_BOOLEAN,
        ],
        ResourceInterface::VALUE_ACCESSOIRES=> [
            'rules'   => 'integer',
        ],
        ResourceInterface::VALUE_AUDIO               => [
            'rules'   => 'integer',
        ],

    ];


    public function __construct()
    {
        parent::__construct();
        $this->documentRequest      = true;
        $this->strictStandardFields = false;

    }

    public function getResult()
    {
        try{
            $result = $this->getPremieResultLegacy('car_option_list', $this->advice, $this->extended);
        } catch(\Exception $e) {
            die($e->getFile().' L'.$e->getLine().': '.$e->getMessage());
        }
        return $result;
    }

    /**
     * LEGACY
     */
    protected function getPremieResultLegacy($optionlList, $advice = null, $extended = false)
    {
        //call license client
        $listRes      = $this->internalRequest('carinsurance', 'list', [ResourceInterface::OPTIONLIST => $optionlList]);
        $ratingMaping = isset($listRes['ratings']) ? $listRes['ratings'] : [];

        /** @var SimpleXMLElement $premObjs */
        $result = parent::getResult();

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
                            continue;
                        }


                        preg_match('/is (\d)./', $val['Acceptatiemeldingen']['Acceptatiemelding']['Omschrijving'], $matches);
                        if( ! isset($matches[1])){
                            continue;
                        }

                        //set the error
                        $val[ResourceInterface::SECURITY_ERROR]   = true;
                        $val[ResourceInterface::SECURITY_MINIMAL] = $matches[1];
                    }
                }
                $assurantieBelasting = 1 + $val['Assurantiebelastingpercentage'] / 10000;

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
                    $val[ResourceInterface::AVERAGE_RATING] = $ratingValue / $ratingCount / 1000;
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
        //dd($returnArr);
        $foundCoverages = [];
        $counter        = - 1;

        $printCoverages = [];

        $strippedArr = [];
        foreach($returnArr as $coverageEntry){
            if( ! in_array($coverageEntry[ResourceInterface::COVERAGE], $foundCoverages)){
                $counter ++;
                $foundCoverages[]                              = $coverageEntry[ResourceInterface::COVERAGE];
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



    public function setParams(Array $params)
    {

        $privateFlag =  ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_ja'));
        if (isset($params[ResourceInterface::INCLUDE_VAT])){
            $privateFlag = ResourceFilterHelper::filterBooleanToInt($params[ResourceInterface::INCLUDE_VAT]) ? ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_ja')) : ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_nee'));
        }

        $dekking = isset($params[ResourceInterface::COVERAGE]) && !is_array($params[ResourceInterface::COVERAGE]) ? $params[ResourceInterface::COVERAGE] : 'all';


        /**
         * Hacks to initialize arrays
         */

        //backwards comp
        if (isset($params[ResourceInterface::RESOURCE_ID]) && count($params[ResourceInterface::RESOURCE_ID])) {
            $params[ResourceInterface::IDS] = $params[ResourceInterface::RESOURCE_ID];
        }

        $idsArray = $params[ResourceInterface::IDS];
        if( ! is_array($idsArray)){
            $idsArray = explode(',', $idsArray);
        }


        /**
         * We don't do request on the full product range any more
         */
        if (count($idsArray) == 0){
            $this->addErrorMessage('global', 'carinsurance.products.empty', 'Rolls product array call, with empty product array', 'input');
            return;
        }

        /**
         * Only do an extended request when there is only one product we call
         */
        $this->extended = (count($idsArray) == 1);
        cw('Extended is '.$this->extended);

        $this->init($this->extended ? ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.aanvullendepremie_auto_function_legacy')) : ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.premie_auto_function_legacy')));

        $node = dom_import_simplexml($this->xml);

        $this->params   = $params;
        $parametersNode = $node->getElementsByTagName('Parameters')->item(0);

        $docSwitch   = $node->ownerDocument->createElement('Switches');
        $docErVoorBC = $node->ownerDocument->createElement('ErVoorBC');
        $docSwitch->appendChild($docErVoorBC);

        $parametersNode->parentNode->insertBefore($docSwitch, $parametersNode);
        $this->xml = simplexml_import_dom($node);

        if(isset($params[ResourceInterface::LICENSEPLATE])){
            cws('Licenseplate_internal');
            //internal request
            $res       = $this->internalRequest('carinsurance', 'licenseplate_legacy', [ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE], 'extended' => 1]);
            if (isset($params[ResourceInterface::CONSTRUCTION_DATE])) {
                $bouwdatum =$params[ResourceInterface::CONSTRUCTION_DATE];
            } else {
                if (isset($res[ResourceInterface::CONSTRUCTION_DATE])) {
                    $bouwdatum =$res[ResourceInterface::CONSTRUCTION_DATE];
                } else {
                    $this->setErrorString('No valid construction date on licenseplate '. $params[ResourceInterface::LICENSEPLATE]);
                    return;
                }
            }
            $bouwdatum = ResourceFilterHelper::filterNumber($bouwdatum);
            if(isset($params[ResourceInterface::TYPE_ID])){
                $type = $params[ResourceInterface::TYPE_ID];
            }else{
                if(isset($res[ResourceInterface::TYPES][0]) && isset($res[ResourceInterface::TYPES][0][ResourceInterface::RESOURCE_ID])){
                    $type = $res[ResourceInterface::TYPES][0][ResourceInterface::RESOURCE_ID];
                }else{
                    $this->setErrorString('No types found for this license plate, probably validation is wrong: ' . $params[ResourceInterface::LICENSEPLATE]);
                    return;
                }
            }
            cwe('Licenseplate_internal');

        }else{
            if (isset($params[ResourceInterface::CONSTRUCTION_DATE] )) {
                $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE] ;
            }else if(isset($params[ResourceInterface::CONSTRUCTION_DATE_MONTH]) && isset($params[ResourceInterface::CONSTRUCTION_DATE_YEAR])) {
                $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE_YEAR] . str_pad($params[ResourceInterface::CONSTRUCTION_DATE_MONTH], 2, "0", STR_PAD_LEFT).'01';
            } else {
                $this->setErrorString('No valid construction date, or year/month combination');
                return;
            }
            if( ! (isset($params[ResourceInterface::TYPE_ID]))){
                $this->setErrorString('Either licenseplate or (type_id and construction_data) is required for calculation');
                return;
            }
            $type      = $params[ResourceInterface::TYPE_ID];

        }
        $this->advice = $this->getDekkingAdvies($bouwdatum);
        $beveiliging = $this->getBeveiliging(date('Y', strtotime($bouwdatum)));


        $geboortedatum     = $params[ResourceInterface::BIRTHDATE];
        $leeftijdrijbewijs = $params[ResourceInterface::DRIVERS_LICENSE_AGE];
        $postcode          = $params[ResourceInterface::POSTAL_CODE];
        $huisnummer        = isset($params[ResourceInterface::HOUSE_NUMBER]) ? $params[ResourceInterface::HOUSE_NUMBER] : false;
        $schadevrij        = $params[ResourceInterface::YEARS_WITHOUT_DAMAGE];
        $eigenrisico       = isset($params[ResourceInterface::OWN_RISK]) && is_numeric($params[ResourceInterface::OWN_RISK]) ? $params[ResourceInterface::OWN_RISK] : 150;
        $kilometerperjaar  = $params[ResourceInterface::MILEAGE];




        $jarenrijbewijs = $this->getJarenRijwewijs($leeftijdrijbewijs, $geboortedatum);
        $jarenverzekerd = $jarenrijbewijs;

        $ingangsdatum       = $this->getNow();

        $betalingstermijn   = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.termijn_maand'));

        //delete overbodige parameters
        $this->deleteParameterTree('Huidigepolis');
        $this->deleteParameterTree('Productselectie');
        $this->deleteParameterTree('Motorpolis');
        $this->setVerzekeringnemerPostcode($postcode);
        $this->setVerzekeringnemerNatuurlijkpersoon($privateFlag);

        //format YYYYMMDD
        $this->setRegelmatigebestuurderGeboortedatum($geboortedatum);

        //format ####CC
        $this->setRegelmatigebestuurderPostcode($postcode);
        if($huisnummer){
            $this->setRegelmatigebestuurderHuisnummer($huisnummer);
        }

        //format 'man' of 'vrouw'
        //$this->setRegelmatigebestuurderGeslacht('man');
        $this->deleteRegelmatigebestuurderGeslacht();
        $this->setRegelmatigebestuurderJarenrijbewijs($jarenrijbewijs);
        $this->setRegelmatigebestuurderJarenschadevrij($schadevrij);

        //verwijder alle standaar geclaimde schuld schade nodes
        $this->deleteRegelmatigeBestuurderGeclaimdeSchuldSchade();
        $this->setRegelmatigebestuurderGeboortedatum($geboortedatum);
        $this->setRegelmatigebestuurderJarenverzekerd($jarenverzekerd);
        $this->deleteRegelmatigebestuurderBeroep();
        $this->deleteRegelmatigebestuurderWerkgever();
        $this->deleteRegelmatigebestuurderDatumrijbewijs();
        $this->deleteRegelmatigebestuurderBmverklaring();

        //voertuig type id
        $this->setVoertuigTypeid($type);
        //nieuwwaarde incl btw
        $this->setVoertuigInclbtw($privateFlag);


        //        $this->setVoertuigGewicht( $gewicht );
        //        $this->setVoertuigVermogen( $vermogen );
        //        $this->setVoertuigBrandstof( $brandstof );
        //        $this->setVoertuigNieuwwaarde( $nieuwwaarde );
        //        $this->setVoertuigDagwaarde( $dagwaarde );
        $this->deleteVoertuigGewicht();
        $this->deleteVoertuigVermogen();
        $this->deleteVoertuigBrandstof();
        $this->deleteVoertuigNieuwwaarde();
        $this->deleteVoertuigDagwaarde();


        $this->setVoertuigBouwdatum($bouwdatum);

        $this->setVoertuigBeveiliging($beveiliging);

        //$this->setVoertuigAantalzitplaatsen($)

        if (isset($params[ResourceInterface::VALUE_ACCESSOIRES])) {
            $this->setVoertuigWaardeaccessoires($params[ResourceInterface::VALUE_ACCESSOIRES]);
        } else {
            $this->deleteVoertuigWaardeaccessoires();
        }
        if (isset($params[ResourceInterface::VALUE_AUDIO])) {
            $this->setVoertuigWaardeaudio($params[ResourceInterface::VALUE_AUDIO]);
        } else {
            $this->deleteVoertuigWaardeaudio();
        }


        $this->deleteVoertuigEersteeigenaar();

        //set polist ingangs datum op vandaag
        $this->setNieuwepolisIngangsdatum($ingangsdatum);
        //zet op particulier gebruik
        $this->setNieuwepolisParticuliergebruik($privateFlag);
        //kilometer stand
        $this->setNieuwepolisKilometrage($kilometerperjaar);
        //default waardes
        //$this->deleteNieuwepolisInclbtw();
        $this->setNieuwepolisInclbtw($privateFlag);

        //
        $this->deleteNieuwepolisNPRebrieken();


        if($dekking != 'all'){
            //premie object
            $this->setPremieobjectenPremieobjectBetalingstermijn($betalingstermijn);
            $this->setPremieobjectenPremieobjectDekking($dekking);
            $this->setPremieobjectenPremieobjectGewensteigenrisico($eigenrisico); // Was standaard 150


            //default inclusief polis kosten
            $this->deletePremieobjectenPremieobjectPoliskostenincl();
            //default in clusief assuratie belastingen
            $this->deletePremieobjectenPremieobjectAssurantiebelastingincl();
            $this->deletePremieobjectenProductselectie();
            $this->deletePremieobjectenPremieobjectContractsduur();
            $this->deletePremieobjectenPremieobjectPremies();

            //show all!
            $this->setPremieobjectenPremieobjectIncludeproductenzonderacceptatie($this->rollsBool('true'));
            if(is_array($idsArray) && sizeof($idsArray) > 0){
                $this->addPremieobjecteProductselectieArray($idsArray);
            }
        }else{
            unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject);
            foreach(['wa', 'bc', 'vc'] as $typeDekking){
                $premieObject = $this->xml->Functie->Parameters->Premieobjecten->addChild('Premieobject');
                $premieObject->addChild('Betalingstermijn', $betalingstermijn);
                $premieObject->addChild('Dekking', $typeDekking);
                $premieObject->addChild('Gewensteigenrisico', $eigenrisico);
                $premieObject->addChild('Prolongatiekostenincl');
                $premieObject->addChild('Extrasincl');
                $premieObject->addChild('Includeproductenzonderacceptatie', $this->rollsBool('true'));
                if(is_array($idsArray) && sizeof($idsArray) > 0){
                    $productSelectie = $premieObject->addChild('PO_Productselectie');
                    foreach($idsArray as $prodid){
                        $productSelect = $productSelectie->addChild('PO_Product');
                        $productSelect->addChild('Id', $prodid);
                    }
                }
            }
        }
        if(isset($params[ResourceInterface::LICENSEPLATE])){
            $this->setVoertuigKenteken($params[ResourceInterface::LICENSEPLATE]);
        }else{
            $this->deleteVoertuigKenteken();
        }
        //set the product_id in case only one id
        if($this->extended){
            $this->setProductid($idsArray[0]);
        }
    }

    public function deleteRegelmatigeBestuurderGeclaimdeSchuldSchade()
    {
        if($this->xml->Functie){
            unset($this->xml->Functie->Parameters->Regelmatigebestuurder->BS_Geclaimdeschuldschades);
        }
    }

    public function deleteNieuwepolisNPRebrieken()
    {
        if($this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken){
            $dom = dom_import_simplexml($this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken);
            $dom->parentNode->removeChild($dom);
        }
    }



    function getBeveiliging($bouwjaar)
    {
        if($bouwjaar >= 1998){
            return 1;
        }
        return 0;
    }


    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */


    public function setProductid($par)
    {
        $this->xml->Functie->Parameters->Productid = $par;
    }

    public function setRegelmatigebestuurderGeboortedatum($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Geboortedatum = $par;
    }

    public function setRegelmatigebestuurderPostcode($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Postcode = $par;
    }

    public function setRegelmatigebestuurderHuisnummer($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Huisnummer = $par;
    }

    public function setRegelmatigebestuurderGeslacht($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Geslacht = $par;
    }

    public function setRegelmatigebestuurderBeroep($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Beroep = $par;
    }

    public function setRegelmatigebestuurderWerkgever($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Werkgever = $par;
    }

    public function setRegelmatigebestuurderDatumrijbewijs($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Datumrijbewijs = $par;
    }

    public function setRegelmatigebestuurderJarenrijbewijs($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenrijbewijs = $par;
    }

    public function setRegelmatigebestuurderJarenschadevrij($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenschadevrij = $par;
    }

    public function setRegelmatigebestuurderJarenverzekerd($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenverzekerd = $par;
    }

    public function setRegelmatigebestuurderBS_GeclaimdeschuldschadesDatum($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->BS_Geclaimdeschuldschades->Datum = $par;
    }

    public function setRegelmatigebestuurderBmverklaring($par)
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Bmverklaring = $par;
    }

    public function setVerzekeringnemerPostcode($par)
    {
        $this->xml->Functie->Parameters->Verzekeringnemer->Postcode = $par;
    }

    public function setVerzekeringnemerNatuurlijkpersoon($par)
    {
        $this->xml->Functie->Parameters->Verzekeringnemer->Natuurlijkpersoon = $par;
    }

    public function setVoertuigBouwdatum($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Bouwdatum = $par;
    }

    public function setVoertuigTypeid($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Typeid = $par;
    }

    public function setVoertuigInclbtw($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Inclbtw = $par;
    }

    public function setVoertuigNieuwwaarde($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Nieuwwaarde = $par;
    }

    public function setVoertuigGewicht($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Gewicht = $par;
    }

    public function setVoertuigDagwaarde($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Dagwaarde = $par;
    }

    public function setVoertuigBrandstof($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Brandstof = $par;
    }

    public function setVoertuigAantalzitplaatsen($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Aantalzitplaatsen = $par;
    }

    public function setVoertuigVermogen($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Vermogen = $par;
    }

    public function setVoertuigBeveiliging($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Beveiliging = $par;
    }

    public function setVoertuigWaardeaccessoires($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Waardeaccessoires = $par;
    }

    public function setVoertuigWaardeaudio($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Waardeaudio = $par;
    }

    public function setVoertuigKenteken($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Kenteken = $par;
    }

    public function setVoertuigEersteeigenaar($par)
    {
        $this->xml->Functie->Parameters->Voertuig->Eersteeigenaar = $par;
    }

    public function setHuidigepolisProductid($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Productid = $par;
    }

    public function setHuidigepolisAanvullingenAanvullingid($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Aanvullingen->Aanvullingid = $par;
    }

    public function setHuidigepolisIngangsdatum($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Ingangsdatum = $par;
    }

    public function setHuidigepolisDekking($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Dekking = $par;
    }

    public function setHuidigepolisBm($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Bm = $par;
    }

    public function setHuidigepolisBmtrede($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Bmtrede = $par;
    }

    public function setHuidigepolisObjectwijziging($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Objectwijziging = $par;
    }

    public function setHuidigepolisBrandstofwijziging($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Brandstofwijziging = $par;
    }

    public function setHuidigepolisAantalvrouwentreden($par)
    {
        $this->xml->Functie->Parameters->Huidigepolis->Aantalvrouwentreden = $par;
    }

    public function setNieuwepolisIngangsdatum($par)
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum = $par;
    }

    public function setNieuwepolisParticuliergebruik($par)
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Particuliergebruik = $par;
    }

    public function setNieuwepolisKilometrage($par)
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Kilometrage = $par;
    }

    public function setNieuwepolisInclbtw($par)
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Inclbtw = $par;
    }

    public function setNieuwepolisNP_RubriekenNP_RubriekId($par)
    {
        $this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken->NP_Rubriek->Id = $par;
    }

    public function setNieuwepolisNP_RubriekenNP_RubriekGewicht($par)
    {
        $this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken->NP_Rubriek->Gewicht = $par;
    }

    public function setMotorpolisProductid($par)
    {
        $this->xml->Functie->Parameters->Motorpolis->Productid = $par;
    }

    public function setMotorpolisJarenschadevrij($par)
    {
        $this->xml->Functie->Parameters->Motorpolis->Jarenschadevrij = $par;
    }

    public function setMotorpolisMP_GeclaimdeschuldschadesDatum($par)
    {
        $this->xml->Functie->Parameters->Motorpolis->MP_Geclaimdeschuldschades->Datum = $par;
    }

    public function setProductselectieProductId($par)
    {
        $this->xml->Functie->Parameters->Productselectie->Product->Id = $par;
    }

    public function setPremieobjectenPremieobjectBetalingstermijn($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn = $par;
    }

    public function setPremieobjectenPremieobjectContractsduur($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Contractsduur = $par;
    }

    public function setPremieobjectenPremieobjectDekking($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Dekking = $par;
    }

    public function setPremieobjectenPremieobjectGewensteigenrisico($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico = $par;
    }

    public function setPremieobjectenPremieobjectPoliskostenincl($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl = $par;
    }

    public function setPremieobjectenPremieobjectAssurantiebelastingincl($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Assurantiebelastingincl = $par;
    }

    public function setPremieobjectenPremieobjectPremies($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies = $par;
    }

    public function setPremieobjectenPremieobjectPO_ProductselectiePO_ProductId($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id = $par;
    }


    ///
    public function deleteRegelmatigebestuurderGeboortedatum()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Geboortedatum);
    }

    public function deleteRegelmatigebestuurderPostcode()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Postcode);
    }

    public function deleteRegelmatigebestuurderGeslacht()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Geslacht);
    }

    public function deleteRegelmatigebestuurderBeroep()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Beroep);
    }

    public function deleteRegelmatigebestuurderWerkgever()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Werkgever);
    }

    public function deleteRegelmatigebestuurderDatumrijbewijs()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Datumrijbewijs);
    }

    public function deleteRegelmatigebestuurderJarenrijbewijs()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenrijbewijs);
    }

    public function deleteRegelmatigebestuurderJarenschadevrij()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenschadevrij);
    }

    public function deleteRegelmatigebestuurderJarenverzekerd()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenverzekerd);
    }

    public function deleteRegelmatigebestuurderBS_GeclaimdeschuldschadesDatum()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->BS_Geclaimdeschuldschades->Datum);
    }

    public function deleteRegelmatigebestuurderBmverklaring()
    {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Bmverklaring);
    }

    public function deleteVerzekeringnemerPostcode()
    {
        unset($this->xml->Functie->Parameters->Verzekeringnemer->Postcode);
    }

    public function deleteVerzekeringnemerNatuurlijkpersoon()
    {
        unset($this->xml->Functie->Parameters->Verzekeringnemer->Natuurlijkpersoon);
    }

    public function deleteVoertuigBouwdatum()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Bouwdatum);
    }

    public function deleteVoertuigTypeid()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Typeid);
    }

    public function deleteVoertuigInclbtw()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Inclbtw);
    }

    public function deleteVoertuigNieuwwaarde()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Nieuwwaarde);
    }

    public function deleteVoertuigGewicht()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Gewicht);
    }

    public function deleteVoertuigDagwaarde()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Dagwaarde);
    }

    public function deleteVoertuigBrandstof()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Brandstof);
    }

    public function deleteVoertuigVermogen()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Vermogen);
    }

    public function deleteVoertuigBeveiliging()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Beveiliging);
    }

    public function deleteVoertuigWaardeaccessoires()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Waardeaccessoires);
    }

    public function deleteVoertuigWaardeaudio()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Waardeaudio);
    }

    public function deleteVoertuigKenteken()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Kenteken);
    }

    public function deleteVoertuigEersteeigenaar()
    {
        unset($this->xml->Functie->Parameters->Voertuig->Eersteeigenaar);
    }

    public function deleteHuidigepolisProductid()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Productid);
    }

    public function deleteHuidigepolisAanvullingenAanvullingid()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Aanvullingen->Aanvullingid);
    }

    public function deleteHuidigepolisIngangsdatum()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Ingangsdatum);
    }

    public function deleteHuidigepolisDekking()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Dekking);
    }

    public function deleteHuidigepolisBm()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Bm);
    }

    public function deleteHuidigepolisBmtrede()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Bmtrede);
    }

    public function deleteHuidigepolisObjectwijziging()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Objectwijziging);
    }

    public function deleteHuidigepolisBrandstofwijziging()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Brandstofwijziging);
    }

    public function deleteHuidigepolisAantalvrouwentreden()
    {
        unset($this->xml->Functie->Parameters->Huidigepolis->Aantalvrouwentreden);
    }

    public function deleteNieuwepolisIngangsdatum()
    {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum);
    }

    public function deleteNieuwepolisParticuliergebruik()
    {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Particuliergebruik);
    }

    public function deleteNieuwepolisKilometrage()
    {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Kilometrage);
    }

    public function deleteNieuwepolisInclbtw()
    {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Inclbtw);
    }

    public function deleteNieuwepolisNP_RubriekenNP_RubriekId()
    {
        unset($this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken->NP_Rubriek->Id);
    }

    public function deleteNieuwepolisNP_RubriekenNP_RubriekGewicht()
    {
        unset($this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken->NP_Rubriek->Gewicht);
    }

    public function deleteMotorpolisProductid()
    {
        unset($this->xml->Functie->Parameters->Motorpolis->Productid);
    }

    public function deleteMotorpolisJarenschadevrij()
    {
        unset($this->xml->Functie->Parameters->Motorpolis->Jarenschadevrij);
    }

    public function deleteMotorpolisMP_GeclaimdeschuldschadesDatum()
    {
        unset($this->xml->Functie->Parameters->Motorpolis->MP_Geclaimdeschuldschades->Datum);
    }

    public function deleteProductselectieProductId()
    {
        unset($this->xml->Functie->Parameters->Productselectie->Product->Id);
    }

    public function deletePremieobjectenPremieobjectBetalingstermijn()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn);
    }

    public function deletePremieobjectenPremieobjectContractsduur()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Contractsduur);
    }

    public function deletePremieobjectenPremieobjectDekking()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Dekking);
    }

    public function deletePremieobjectenPremieobjectGewensteigenrisico()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico);
    }

    public function deletePremieobjectenPremieobjectPoliskostenincl()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl);
    }

    public function deletePremieobjectenPremieobjectAssurantiebelastingincl()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Assurantiebelastingincl);
    }

    public function deletePremieobjectenPremieobjectPremies()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies);
    }

    public function deletePremieobjectenPremieobjectPO_ProductselectiePO_ProductId()
    {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id);
    }

    public function setPremieobjectenPremieobjectIncludeproductenzonderacceptatie($par)
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Includeproductenzonderacceptatie = $par;
    }


}