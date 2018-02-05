<?php
/**
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
use Illuminate\Support\Facades\Cache;


class BestelautoPremieBerekenClient extends RollsAbstractSoapRequest
{
    protected $cacheDays = 1;

    protected $extended = false;

    protected $licenseplate = false;
    protected $construction_date = false;

    protected $arguments = [
        ResourceInterface::BIRTHDATE            => [
            'rules'   => self::VALIDATION_REQUIRED_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::DRIVERS_LICENSE_AGE  => [
            'rules'   => 'required | integer',
            'example' => '19',
        ],
        ResourceInterface::COVERAGE             => [
            'rules'   => 'required',
            'example' => 'bc, vc or wa. Use all for all premiums',
        ],
        ResourceInterface::OWN_RISK             => [
            'rules'   => 'integer',
            'example' => '0,150,300,999',
        ],
        ResourceInterface::MILEAGE              => [
            'rules'   => 'required | integer',
            'example' => '7500,10000,12000,15000,20000,25000,30000,90000',
        ],
        ResourceInterface::YEARS_WITHOUT_DAMAGE => [
            'rules'   => 'required | number',
            'example' => '10',
        ],
        ResourceInterface::POSTAL_CODE          => [
            'rules'   => self::VALIDATION_REQUIRED_POSTAL_CODE,
            'example' => '8014EH',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::TYPE_ID              => [
            'rules'   => 'number',
            'example' => '84654',
        ],
        ResourceInterface::LICENSEPLATE         => [
            'rules'   => self::VALIDATION_LICENSEPLATE,
            'example' => '06-VRJ-2',
            'filter'  => 'filterAlfaNumber'
        ],
        ResourceInterface::CONSTRUCTION_DATE    => [
            'rules'   => 'string',
            'example' => '2009-04-01',
        ],
        ResourceInterface::CONSTRUCTION_DATE_MONTH      => [
            'rules'   => 'number',
            'example' => '03',
        ],
        ResourceInterface::CONSTRUCTION_DATE_YEAR       => [
            'rules'   => 'number',
            'example' => '2012',
        ],
        ResourceInterface::HOUSE_NUMBER                 => [
            'rules'   => 'integer',
            'example' => '21'
        ],
        ResourceInterface::INCLUDE_VAT                 => [
            'rules'   => 'bool',
        ],
        ResourceInterface::PRIVATE_USE                 => [
            'rules'   => 'bool',
        ],
        ResourceInterface::TRANSPORT_GOODS_TYPE                 => [
            'rules'   => 'string',
        ],
        ResourceInterface::BRANCH                 => [
            'rules'   => 'string',
        ],
        ResourceInterface::EXCLUDE_BPM                 => [
            'rules'   => 'bool',
        ],
        ResourceInterface::IDS                  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
            'default'       => [],
        ],
        ResourceInterface::RESOURCE_ID                  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
            'default' => []
        ],
    ];


    public function __construct()
    {
        parent::__construct();
        $this->strictStandardFields = false;
    }

    public function getResult()
    {
        $advice = false;

        if($this->licenseplate){
            $carResult = $this->getCarResult($this->getCarDetails(), 'van_option_list');

            if (isset($carResult, $carResult[ResourceInterface::CONSTRUCTION_DATE]))
                $carResult[ResourceInterface::ADVISE] = $this->getDekkingAdvies($carResult[ResourceInterface::CONSTRUCTION_DATE]);

            // This cache is used by AutoBijKentekenViaPremieClient
            Cache::put('rolls_viapremie_licenseplate-'. strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $this->licenseplate)), $carResult, 20000);

            if (isset($carResult[ResourceInterface::ADVISE]))
                $advice = $carResult[ResourceInterface::ADVISE];
        } else {
            $advice = $this->getDekkingAdvies($this->construction_date);
        }

        return $this->getMotorizedPremieResult('van_option_list', $this->extended, $advice);
    }


    public function setParams( Array $params )
    {
        $privateFlag = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_ja'));
        if(isset($params[ResourceInterface::INCLUDE_VAT])){
            $privateFlag = ResourceFilterHelper::filterBooleanToInt($params[ResourceInterface::INCLUDE_VAT]) ? ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_ja')) : ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.optie_nee'));
        }

        $dekking = isset($params[ResourceInterface::COVERAGE]) && ! is_array($params[ResourceInterface::COVERAGE]) ? $params[ResourceInterface::COVERAGE] : 'all';

        /**
         * Load custom office ID
         */
        if(isset($params[ResourceInterface::OFFICE_ID])) {
            $this->officeId = $params[ResourceInterface::OFFICE_ID];
        }

        /**
         * Hacks to initialize arrays
         */

        //backwards comp
        if(isset($params[ResourceInterface::RESOURCE_ID]) && count($params[ResourceInterface::RESOURCE_ID])){
            $params[ResourceInterface::IDS] = $params[ResourceInterface::RESOURCE_ID];
        }

        $idsArray = $params[ResourceInterface::IDS];
        if( ! is_array($idsArray)){
            $idsArray = explode(',', $idsArray);
        }


        /**
         * We don't do request on the full product range any more
         */
        if(count($idsArray) == 0){
            //$this->addErrorMessage('global', 'vaninsurance.products.empty', 'Rolls product array call, with empty product array', 'input');
            //return;
        }

        /**
         * Only do an extended request when there is only one product we call
         */
        $this->extended = (count($idsArray) == 1);
        cw('Extended is ' . $this->extended);

        $this->init($this->extended ? ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.aanvullendepremie_bestelauto_function')) : ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.premie_bestelauto_function')));

        $node = dom_import_simplexml($this->xml);

        $this->params   = $params;
        $parametersNode = $node->getElementsByTagName('Parameters')->item(0);

        $docSwitch   = $node->ownerDocument->createElement('Switches');
        $docErVoorBC = $node->ownerDocument->createElement('ErVoorBC');
        $docSwitch->appendChild($docErVoorBC);

        $parametersNode->parentNode->insertBefore($docSwitch, $parametersNode);
        $this->xml = simplexml_import_dom($node);

        if(isset($params[ResourceInterface::CONSTRUCTION_DATE])){
            $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE];
        }else if(isset($params[ResourceInterface::CONSTRUCTION_DATE_MONTH]) && isset($params[ResourceInterface::CONSTRUCTION_DATE_YEAR])){
            $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE_YEAR] . str_pad($params[ResourceInterface::CONSTRUCTION_DATE_MONTH], 2, "0", STR_PAD_LEFT) . '01';
        }else{
            $bouwdatum = false;
        }
        $this->construction_date = $bouwdatum;
        if(isset($params[ResourceInterface::LICENSEPLATE])){
            $this->licenseplate = $params[ResourceInterface::LICENSEPLATE];
            $this->deleteVoertuigTypeid();

        }else{
            if( ! (isset($params[ResourceInterface::TYPE_ID]))){
                $this->setErrorString('Either licenseplate or (type_id and construction_data) is required for calculation');
                return;
            }
            //voertuig type id
            $this->setVoertuigTypeid($params[ResourceInterface::TYPE_ID]);

        }

        if (!isset($params[ResourceInterface::OWN_RISK]))
            $eigenRisicos = [150];
        else if (str_contains($params[ResourceInterface::OWN_RISK], ','))
            $eigenRisicos = explode(',', $params[ResourceInterface::OWN_RISK]);
        else if (!is_numeric($params[ResourceInterface::OWN_RISK]))
            $eigenRisicos = [150];
        else
            $eigenRisicos = [$params[ResourceInterface::OWN_RISK]];

        $geboortedatum     = $params[ResourceInterface::BIRTHDATE];
        $leeftijdrijbewijs = $params[ResourceInterface::DRIVERS_LICENSE_AGE];
        $postcode          = $params[ResourceInterface::POSTAL_CODE];
        $huisnummer        = isset($params[ResourceInterface::HOUSE_NUMBER]) ? $params[ResourceInterface::HOUSE_NUMBER] : false;
        $schadevrij        = $params[ResourceInterface::YEARS_WITHOUT_DAMAGE];
        $kilometerperjaar  = $params[ResourceInterface::MILEAGE];

        $dagwaarde   = isset($params[ResourceInterface::DAILY_VALUE]) ? $params[ResourceInterface::DAILY_VALUE] : null;
        $nieuwwaarde = isset($params[ResourceInterface::REPLACEMENT_VALUE]) ? $params[ResourceInterface::REPLACEMENT_VALUE] : null;


        $jarenrijbewijs = $this->getJarenRijwewijs($leeftijdrijbewijs, $geboortedatum);
        $jarenverzekerd = $jarenrijbewijs;

        $ingangsdatum = $this->getNow();

        $betalingstermijn = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.termijn_maand'));

        //delete overbodige parameters
        $this->deleteParameterTree('Huidigepolis');
        $this->deleteParameterTree('Productselectie');
        $this->deleteParameterTree('Motorpolis');
        $this->setRegelmatigebestuurderPostcode($postcode);
        $this->setNieuwepolisParticuliergebruik(empty($params[ResourceInterface::PRIVATE_USE]) ? 'nee' : 'ja');

        if (isset($params[ResourceInterface::TRANSPORT_GOODS_TYPE]))
            $this->setNieuwepolisVervoervan($params[ResourceInterface::TRANSPORT_GOODS_TYPE]);
        else
            $this->deleteNieuwepolisVervoervan();

        if (isset($params[ResourceInterface::EXCLUDE_BPM]))
            $this->setNieuwepolisBpmterugvorderbaar(empty($params[ResourceInterface::EXCLUDE_BPM]) ? 'nee' : 'ja');
        else
            $this->deleteNieuwepolisBpmterugvorderbaar();

        $this->deletePremieobjectenPremieobjectPoliskostenincl();
        $this->deletePremieobjectenPremieobjectAssurantiebelastingincl();
        $this->deletePremieobjectenProductselectie();
        $this->deleteVoertuigBeveiliging();

        //format YYYYMMDD
        $this->setRegelmatigebestuurderGeboortedatum($geboortedatum);

        //format ####CC
        $this->setRegelmatigebestuurderPostcode($postcode);
        if($huisnummer){
            $this->setRegelmatigebestuurderHuisnummer($huisnummer);
        }else{
            $this->deleteRegelmatigebestuurderHuisnummer();
        }

        if (isset($params[ResourceInterface::BRANCH]))
            $this->setRegelmatigebestuurderBranche($params[ResourceInterface::BRANCH]);

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
        //$this->deleteRegelmatigebestuurderWerkgever();
        $this->deleteRegelmatigebestuurderDatumrijbewijs();
        $this->deleteRegelmatigebestuurderBmverklaring();

        //nieuwwaarde incl btw
        $this->deleteVoertuigInclbtw();


        if($dagwaarde){
            $this->setVoertuigDagwaarde($dagwaarde);
        }
        if($nieuwwaarde){
            $this->setVoertuigNieuwwaarde($nieuwwaarde);
        }
        //$this->deleteVoertuigGewicht();
        $this->deleteVoertuigVermogen();
        $this->deleteVoertuigBrandstof();
        $this->deleteVoertuigNieuwwaarde();
        $this->deleteVoertuigDagwaarde();

        if($bouwdatum){
            $this->setVoertuigBouwdatum($bouwdatum);
        }else{
            $this->deleteVoertuigBouwdatum();
        }

        $this->deleteVoertuigBeveiliging();

        //$this->setVoertuigAantalzitplaatsen($)

        if(isset($params[ResourceInterface::VALUE_ACCESSOIRES])){
            $this->setVoertuigWaardeaccessoires($params[ResourceInterface::VALUE_ACCESSOIRES]);
        }else{
            $this->deleteVoertuigWaardeaccessoires();
        }

        //set polist ingangs datum op vandaag
        $this->setNieuwepolisIngangsdatum($ingangsdatum);
        //kilometer stand
        $this->setNieuwepolisKilometrage($kilometerperjaar);
        //default waardes
        //$this->deleteNieuwepolisInclbtw();

        $this->setNieuwepolisInclbtw($params[ResourceInterface::INCLUDE_VAT] == false || $params[ResourceInterface::INCLUDE_VAT] === 'false' ? 'nee' : 'ja');

        $this->deleteNieuwepolisNPRebrieken();

        $this->setVerzekeringnemerPostcode($postcode);
        $this->setVerzekeringnemerNatuurlijkpersoon($privateFlag);

        if($dekking != 'all' && count($eigenRisicos) === 1){
            //premie object
            $this->setPremieobjectenPremieobjectBetalingstermijn($betalingstermijn);
            $this->setPremieobjectenPremieobjectDekking($dekking);
            $this->setPremieobjectenPremieobjectGewensteigenrisico($eigenRisicos[0]); // Was standaard 150


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

            foreach (['wa', 'bc', 'vc'] as $typeDekking) {
                foreach($eigenRisicos as $nr => $eigenRisico) {

                    // WA only has one eigen risico, so skip the rest
                    if ($typeDekking == 'wa' && $nr > 0)
                        continue;

                    $premieObject = $this->xml->Functie->Parameters->Premieobjecten->addChild('Premieobject');
                    $premieObject->addChild('Betalingstermijn', $betalingstermijn);
                    $premieObject->addChild('Dekking', $typeDekking);
                    $premieObject->addChild('Gewensteigenrisico', $eigenRisico);
                    $premieObject->addChild('Prolongatiekostenincl');
                    $premieObject->addChild('Extrasincl');
                    $premieObject->addChild('Includeproductenzonderacceptatie', $this->rollsBool('true'));
                    if (is_array($idsArray) && sizeof($idsArray) > 0) {
                        $productSelectie = $premieObject->addChild('PO_Productselectie');
                        foreach ($idsArray as $prodid) {
                            $productSelect = $productSelectie->addChild('PO_Product');
                            $productSelect->addChild('Id', $prodid);
                        }
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
        if ($this->extended) {
            $this->setProductid($idsArray[0]);
        } else if (count($idsArray) > 0) {
            $this->addPremieobjecteProductselectieArray($idsArray);
        }
        //var_dump($this->xml->asXML());
    }



    function getJarenRijwewijs( $leeftijdrijbewijs, $geboortedatum )
    {
        //In:yyyymmdd
        $age            = $this->getAge( $geboortedatum );
        $jarenrijbewijs = ( $age - $leeftijdrijbewijs );
        if ($jarenrijbewijs < 0) {
            return null;
        }
        return $jarenrijbewijs;
    }

    function getBeveiliging( $bouwjaar )
    {
        if ($bouwjaar >= 1998) {
            return 1;
        }
        return 0;
    }


    /**
     * @param string $optionlList
     *
     * @return array
     */
    protected function getCarResult($result, $optionList = 'van_option_list')
    {
        if( ! isset($result->Merk) || ! isset($result->Merkid)){
            $this->setErrorString('invalid licenseplate');
            return null;
        }

        $listRes = $this->internalRequest('carinsurance', 'list', [ResourceInterface::OPTIONLIST => $optionList]);

        $return[ResourceInterface::BRAND_NAME]          = $result->Merk . "";
        $return[ResourceInterface::BRAND_ID]            = $result->Merkid . "";
        $return[ResourceInterface::MODEL_NAME]          = $result->Model . "";
        $return[ResourceInterface::MODEL_ID]            = $result->Modelid . "";
        $return[ResourceInterface::CONSTRUCTION_DATE]   = substr($result->Kentekendatumdeel1, 0, 4) . '-' . substr($result->Kentekendatumdeel1, 4, 2) . '-' . substr($result->Kentekendatumdeel1, 6, 2);
        $return[ResourceInterface::TYPE_ID]             = $result->Id . "";
        $return[ResourceInterface::TYPE_NAME]           = $result->Naam . "";
        $return[ResourceInterface::COACHWORK_TYPE_ID]   = $listRes[ResourceInterface::COACHWORK_TYPE_ID][$result->Koetswerk . ""]["name"];
        $return[ResourceInterface::FUEL_TYPE_ID]        = $this->fuelTypeMapping[$result->Kentekenbrandstofid . ""];
        $return[ResourceInterface::WEIGHT]              = $result->Lediggewicht . "";
        $return[ResourceInterface::LICENSEPLATE_WEIGHT] = $result->Kentekengewicht . "";
        $return[ResourceInterface::IMPORTED_CAR]        = null; // Not relevant for vans
        $return[ResourceInterface::LICENSEPLATE_COLOR]  = null; // Not relevant for vans
        $return[ResourceInterface::TRANSMISSION_ID]     = $listRes[ResourceInterface::TRANSMISSION_ID][$result->Transmissie . ""]["name"];
        $return[ResourceInterface::TURBO]               = $result->Turbo . "";
        $return[ResourceInterface::POWER]               = round($result->Vermogen * 1.359623);
        $return[ResourceInterface::CYLINDERS]           = $result->Cylinders . "";
        $return[ResourceInterface::AMOUNT_OF_DOORS]     = null; // Not available for vans
        $return[ResourceInterface::AMOUNT_OF_SEATS]     = $result->Aantalzitplaatsen . "";
        $return[ResourceInterface::REPLACEMENT_VALUE]   = $result->Rollsnieuwwaardebruto . "";
        $return[ResourceInterface::CYLINDER_VOLUME]     = $result->Cilinderinhoud . "";
        $return[ResourceInterface::DAILY_VALUE]         = $result->Dagwaardeinclbtw . "";
        $return[ResourceInterface::SECURITY_CLASS_ID]   = $result->Beveiligingsklasse . "";
        $return[ResourceInterface::ENERGY_LABEL]        = null; // Not available for vans
        $return[ResourceInterface::TOP_SPEED]           = null; // Not available for vans
        $return[ResourceInterface::ACCELERATION]        = null; // Not available for vans

        $return[ResourceInterface::LABEL] = "{$result->Naam} ({$return[ResourceInterface::POWER]} PK)";

        return json_decode(json_encode($return, JSON_NUMERIC_CHECK), true);
    }

    /**
     * Get all car details that were added to this premium
     * @return mixed
     */
    protected function getCarDetails()
    {
        return $this->result->Voertuigdetails;
    }


    // KS305704
    public function deleteRegelmatigeBestuurderGeclaimdeSchuldSchade()
    {
        if ($this->xml->Functie) {
            unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->BS_Geclaimdeschuldschades );
        }
    }

    public function deleteNieuwepolisNPRebrieken()
    {
        if ($this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken) {
            $dom = dom_import_simplexml( $this->xml->Functie->Parameters->Nieuwepolis->NP_Rubrieken );
            $dom->parentNode->removeChild( $dom );
        }
    }

    public function addPremieobjecteProductselectieArray( $productarray )
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product );
        foreach ($productarray as $prodid) {
            $product = $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->addChild( 'PO_Product' );
            $product->addChild( 'Id', $prodid );
        }
    }

    public function deletePremieobjectenProductselectie()
    {
        $node = $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie;
        if ($node) {
            $dom = dom_import_simplexml( $node );
            $dom->parentNode->removeChild( $dom );
        }
    }

    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setProductid($par)
    {
        $this->xml->Functie->Parameters->Productid = $par;
    }

    public function setRegelmatigebestuurderGeboortedatum( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Geboortedatum = $par;
    }

    public function setRegelmatigebestuurderPostcode( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Postcode = $par;
    }

    public function setRegelmatigebestuurderHuisnummer( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Huisnummer = $par;
    }

    public function setRegelmatigebestuurderGeslacht( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Geslacht = $par;
    }

    public function setRegelmatigebestuurderBeroep( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Beroep = $par;
    }

    public function setRegelmatigebestuurderBranche( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Branche = $par;
    }

    public function setRegelmatigebestuurderDatumrijbewijs( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Datumrijbewijs = $par;
    }

    public function setRegelmatigebestuurderJarenrijbewijs( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenrijbewijs = $par;
    }

    public function setRegelmatigebestuurderJarenschadevrij( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenschadevrij = $par;
    }

    public function setRegelmatigebestuurderJarenverzekerd( $par )
    {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenverzekerd = $par;
    }

    public function setRegelmatigebestuurderBmverklaring( $par )
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

    public function setVoertuigBouwdatum( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Bouwdatum = $par;
    }

    public function setVoertuigTypeid( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Typeid = $par;
    }

    public function setVoertuigInclbtw( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Inclbtw = $par;
    }

    public function setVoertuigNieuwwaarde( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Nieuwwaarde = $par;
    }

    public function setVoertuigOorspronkelijkebpm( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Oorspronkelijkebpm = $par;
    }

    public function setVoertuigLediggewicht( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Lediggewicht = $par;
    }

    public function setVoertuigTreingewicht( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Treingewicht = $par;
    }

    public function setVoertuigDagwaarde( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Dagwaarde = $par;
    }

    public function setVoertuigBrandstof( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Brandstof = $par;
    }

    public function setVoertuigVermogen( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Vermogen = $par;
    }

    public function setVoertuigBeveiliging( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Beveiliging = $par;
    }

    public function setVoertuigWaardeaanhanger( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Waardeaanhanger = $par;
    }

    public function setVoertuigWaardeaccessoires( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Waardeaccessoires = $par;
    }

    public function setVoertuigKenteken( $par )
    {
        $this->xml->Functie->Parameters->Voertuig->Kenteken = $par;
    }

    public function setNieuwepolisIngangsdatum( $par )
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum = $par;
    }

    public function setNieuwepolisParticuliergebruik( $par )
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Particuliergebruik = $par;
    }

    public function setNieuwepolisKilometrage( $par )
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Kilometrage = $par;
    }

    public function setNieuwepolisInclbtw( $par )
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Inclbtw = $par;
    }

    public function setNieuwepolisVervoervan( $par )
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Vervoervan = $par;
    }

    public function setNieuwepolisBpmterugvorderbaar( $par )
    {
        $this->xml->Functie->Parameters->Nieuwepolis->Bpmterugvorderbaar = $par;
    }

    public function setPremieobjectenPremieobjectBetalingstermijn( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn = $par;
    }

    public function setPremieobjectenPremieobjectContractsduur( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Contractsduur = $par;
    }

    public function setPremieobjectenPremieobjectDekking( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Dekking = $par;
    }

    public function setPremieobjectenPremieobjectPoliskostenincl( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl = $par;
    }

    public function setPremieobjectenPremieobjectAssurantiebelastingincl( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Assurantiebelastingincl = $par;
    }

    public function setPremieobjectenPremieobjectProlongatiekostenincl( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Prolongatiekostenincl = $par;
    }

    public function setPremieobjectenPremieobjectExtrasincl( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Extrasincl = $par;
    }

    public function setPremieobjectenPremieobjectPremies( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies = $par;
    }

    public function setPremieobjectenPremieobjectPO_ProductselectiePO_ProductId( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id = $par;
    }

    public function setPremieobjectenPremieobjectGewensteigenrisico( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico = $par;
    }

    public function deleteRegelmatigebestuurderGeboortedatum()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Geboortedatum );
    }

    public function deleteRegelmatigebestuurderPostcode()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Postcode );
    }

    public function deleteRegelmatigebestuurderHuisnummer()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Huisnummer );
    }

    public function deleteRegelmatigebestuurderGeslacht()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Geslacht );
    }

    public function deleteRegelmatigebestuurderBeroep()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Beroep );
    }

    public function deleteRegelmatigebestuurderBranche()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Branche );
    }

    public function deleteRegelmatigebestuurderDatumrijbewijs()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Datumrijbewijs );
    }

    public function deleteRegelmatigebestuurderJarenrijbewijs()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenrijbewijs );
    }

    public function deleteRegelmatigebestuurderJarenschadevrij()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenschadevrij );
    }

    public function deleteRegelmatigebestuurderJarenverzekerd()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenverzekerd );
    }

    public function deleteRegelmatigebestuurderBmverklaring()
    {
        unset( $this->xml->Functie->Parameters->Regelmatigebestuurder->Bmverklaring );
    }

    public function deleteVoertuigBouwdatum()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Bouwdatum );
    }

    public function deleteVoertuigTypeid()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Typeid );
    }

    public function deleteVoertuigInclbtw()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Inclbtw );
    }

    public function deleteVoertuigNieuwwaarde()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Nieuwwaarde );
    }

    public function deleteVoertuigOorspronkelijkebpm()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Oorspronkelijkebpm );
    }

    public function deleteVoertuigLediggewicht()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Lediggewicht );
    }

    public function deleteVoertuigTreingewicht()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Treingewicht );
    }

    public function deleteVoertuigDagwaarde()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Dagwaarde );
    }

    public function deleteVoertuigBrandstof()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Brandstof );
    }

    public function deleteVoertuigVermogen()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Vermogen );
    }

    public function deleteVoertuigBeveiliging()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Beveiliging );
    }

    public function deleteVoertuigWaardeaanhanger()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Waardeaanhanger );
    }

    public function deleteVoertuigWaardeaccessoires()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Waardeaccessoires );
    }

    public function deleteVoertuigKenteken()
    {
        unset( $this->xml->Functie->Parameters->Voertuig->Kenteken );
    }

    public function deleteNieuwepolisIngangsdatum()
    {
        unset( $this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum );
    }

    public function deleteNieuwepolisParticuliergebruik()
    {
        unset( $this->xml->Functie->Parameters->Nieuwepolis->Particuliergebruik );
    }

    public function deleteNieuwepolisKilometrage()
    {
        unset( $this->xml->Functie->Parameters->Nieuwepolis->Kilometrage );
    }

    public function deleteNieuwepolisInclbtw()
    {
        unset( $this->xml->Functie->Parameters->Nieuwepolis->Inclbtw );
    }

    public function deleteNieuwepolisVervoervan()
    {
        unset( $this->xml->Functie->Parameters->Nieuwepolis->Vervoervan );
    }

    public function deleteNieuwepolisBpmterugvorderbaar()
    {
        unset( $this->xml->Functie->Parameters->Nieuwepolis->Bpmterugvorderbaar );
    }

    public function deletePremieobjectenPremieobjectBetalingstermijn()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn );
    }

    public function deletePremieobjectenPremieobjectContractsduur()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Contractsduur );
    }

    public function deletePremieobjectenPremieobjectDekking()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Dekking );
    }

    public function deletePremieobjectenPremieobjectPoliskostenincl()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl );
    }

    public function deletePremieobjectenPremieobjectAssurantiebelastingincl()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Assurantiebelastingincl );
    }

    public function deletePremieobjectenPremieobjectProlongatiekostenincl()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Prolongatiekostenincl );
    }

    public function deletePremieobjectenPremieobjectExtrasincl()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Extrasincl );
    }

    public function deletePremieobjectenPremieobjectPremies()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies );
    }

    public function deletePremieobjectenPremieobjectPO_ProductselectiePO_ProductId()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id );
    }

    public function deletePremieobjectenPremieobjectGewensteigenrisico()
    {
        unset( $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico );
    }

    public function setPremieobjectenPremieobjectIncludeproductenzonderacceptatie( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Includeproductenzonderacceptatie = $par;
    }

}