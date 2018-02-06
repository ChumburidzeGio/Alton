<?php
/**
 * (C) 2010 Vergelijken.net
 * User: RuleKinG
 * Date: 17-aug-2010
 * Time: 0:19:25
 */

namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class MotorPremieBerekenClient extends RollsAbstractSoapRequest
{

    protected $cacheDays = 30;

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
            'rules'   => 'required | in:bc,vc,wa',
            'example' => 'bc, vc or wa',
        ],
        ResourceInterface::OWN_RISK             => [
            'rules'   => 'required | in:0,150,300,999',
            'example' => '0,150,300,999',
        ],
        ResourceInterface::MILEAGE              => [
            'rules'   => 'required | in:7500,10000,12000,15000,20000,25000,30000,90000',
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
            'example' => 'MG-60-XX',
            'filter'  => 'filterAlfaNumber'
        ],
        ResourceInterface::CONSTRUCTION_DATE    => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '2009-04-01',
            'filter'  => 'filterNumber'
        ],
        ResourceInterface::IDS                  => [
            'rules'   => 'array',
            'example' => '[213,345345,2342,12341234,1234]',
            'default'       => '[]'
        ],
    ];

    protected $outputFields = [
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::OWN_RISK,
        ResourceInterface::TOTAL_RATING,
        ResourceInterface::RATINGS,
    ];


    public function __construct()
    {
        parent::__construct();
        $this->documentRequest = true;
        $this->init( Config::get( 'resource_rolls.functions.premie_motor_function' ) );
    }

    public function getResult()
    {
        return $this->getMotorizedPremieResult('motorcycle_option_list');
    }



    public function setParams( Array $params )
    {
        //old working code do not fucking touch
        if (isset( $params[ResourceInterface::LICENSEPLATE] )) {
            $res           = $this->internalRequest('motorcycleinsurance','licenseplate', [ ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE]]);
            $bouwdatum = isset( $params[ResourceInterface::CONSTRUCTION_DATE] ) ? $params[ResourceInterface::CONSTRUCTION_DATE] : $res[ResourceInterface::CONSTRUCTION_DATE];
            $type      = isset( $params[ResourceInterface::TYPE_ID] ) ? $params[ResourceInterface::TYPE_ID] : $res[ResourceInterface::TYPES][0][ResourceInterface::RESOURCE_ID] ;
        } else {
            if ( ! ( isset( $params[ResourceInterface::TYPE_ID] ) && isset( $params[ResourceInterface::CONSTRUCTION_DATE] ) )) {
                $this->setErrorString( 'Either licenseplate or (type_id and construction_data) is required for calculation' );
                return;
            }
            $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE];
            $type      = $params[ResourceInterface::TYPE_ID];

        }
        $geboortedatum     = $params[ResourceInterface::BIRTHDATE]  ;
        $leeftijdrijbewijs = $params['drivers_license_age'];
        $postcode          = $params[ResourceInterface::POSTAL_CODE];
        $schadevrij        = $params[ResourceInterface::YEARS_WITHOUT_DAMAGE];
        $eigenrisico       = isset( $params[ResourceInterface::OWN_RISK] ) && is_numeric( $params[ResourceInterface::OWN_RISK] ) ? $params[ResourceInterface::OWN_RISK] : 150;
        $kilometerperjaar  = $params[ResourceInterface::MILEAGE];
        $dekking           = $params[ResourceInterface::COVERAGE];


        $IDS       = $params[ResourceInterface::IDS];
        $beveiliging       = $this->getBeveiliging( date( 'Y', strtotime( $bouwdatum ) ) );


        $jarenrijbewijs = $this->getJarenRijwewijs( $leeftijdrijbewijs, $geboortedatum );
        $jarenverzekerd = $jarenrijbewijs;

        $ingangsdatum       = $this->getNow();
        $betalingstermijn   = Config::get( 'resource_rolls.options.termijn_maand' );

        //delete overbodige parameters
        $this->deleteParameterTree( 'Huidigepolis' );
        $this->deleteParameterTree( 'Productselectie' );
        $this->deleteParameterTree( 'Motorpolis' );
        $this->deleteParameterTree( 'Verzekeringnemer' );


        $this->deleteRegelmatigebestuurderDatumknmvvro();
        $this->deleteRegelmatigebestuurderDatumknmvvrt();
        $this->deleteRegelmatigebestuurderDatumnvvm();
        $this->deleteRegelmatigebestuurderBmverklaring();


        $this->deleteParameterSubTree('Regelmatigebestuurder','BS_Geclaimdeschuldschades');

        //format YYYYMMDD
        $this->setRegelmatigebestuurderGeboortedatum( $geboortedatum );

        //format ####CC
        $this->setRegelmatigebestuurderPostcode( $postcode );

        //format 'man' of 'vrouw'
        //$this->setRegelmatigebestuurderGeslacht('man');
        $this->deleteRegelmatigebestuurderGeslacht();
        $this->setRegelmatigebestuurderJarenrijbewijs( $jarenrijbewijs );
        $this->setRegelmatigebestuurderJarenschadevrij( $schadevrij );

        //verwijder alle standaar geclaimde schuld schade nodes
        $this->setRegelmatigebestuurderGeboortedatum( $geboortedatum );
        $this->setRegelmatigebestuurderJarenverzekerd( $jarenverzekerd );
        $this->deleteRegelmatigebestuurderBeroep();
        $this->deleteRegelmatigebestuurderWerkgever();
        $this->deleteRegelmatigebestuurderDatumrijbewijs();


        //voertuig type id
        $this->setVoertuigTypeid( $type );
        //nieuwwaarde incl btw
        $this->setVoertuigInclbtw( Config::get( 'resource_rolls.options.optie_ja' ) );
        $this->deleteVoertuigGewicht();
        $this->deleteVoertuigVermogen();
        $this->deleteVoertuigNieuwwaarde();
        $this->deleteVoertuigDagwaarde();

        $this->setVoertuigBeveiliging( $beveiliging );
        $this->setVoertuigBouwdatum( $bouwdatum );
        if(isset($params[ResourceInterface::LICENSEPLATE])){
            $this->setVoertuigKenteken($params[ResourceInterface::LICENSEPLATE]);
        }else{
            $this->deleteVoertuigKenteken();
        }

        //$this->setVoertuigAantalzitplaatsen($)
        $this->deleteVoertuigWaardeaccessoires();
        $this->deleteVoertuigEersteeigenaar();

        //set polist ingangs datum op vandaag
        $this->setNieuwepolisIngangsdatum( $ingangsdatum );
        //kilometer stand
        $this->setNieuwepolisKilometrage( $kilometerperjaar );
        //
        $this->deleteParameterTree('Autopolis');

        //premie object
        $this->setPremieobjectenPremieobjectBetalingstermijn( $betalingstermijn );
        $this->setPremieobjectenPremieobjectDekking( $dekking );
        $this->deletePremieobjectenPremieobjectGewensteigenrisico();
        $this->setPremieobjectenPremieobjectGewensteigenrisico( $eigenrisico ); // Was standaard 150
        $this->deletePremieobjectenProductselectie();
        $this->deletePremieobjectenPremieobjectContractsduur();
        $this->deletePremieobjectenPremieobjectPremies();
        $this->deletePremieobjectenPremieobjectWinterstopregeling();
        $this->deletePremieobjectenPremieobjectWinterstopperiode();
        $this->deletePremieobjectenPremieobjectPoliskostenincl();
        $this->deletePremieobjectenPremieobjectAssurantiebelastingincl();

        $this->setPremieobjectenPremieobjectIncludeproductenzonderacceptatie($this->rollsBool('false'));

        if (is_array( $IDS ) && sizeof( $IDS ) > 1) {
            $this->addPremieobjecteProductselectieArray( $IDS );
        }
    }





    //    � auto's jonger dan 6 jaar: WA + Volledig Casco
    //    � auto's tussen de 6 en 8 jaar oud: WA + Beperkt Casco
    //    � auto's ouder dan 8 jaar
    function getDekkingAdvies( $age )
    {
        if ($age < 6) {
            return Config::get( 'resource_rolls.options.dekking_vc' );
        }
        if ($age <= 8) {
            return Config::get( 'resource_rolls.options.dekking_bc' );
        }
        return Config::get( 'resource_rolls.options.dekking_wa' );
    }

    function getBeveiliging( $bouwjaar )
    {
        if ($bouwjaar >= 1998) {
            return 1;
        }
        return 0;
    }

    /**
     * Auto generated functions from XML file 1.0
     *(C) 2010 Vergelijken.net
     */

    public function setRegelmatigebestuurderGeboortedatum($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Geboortedatum = $par;
    }

    public function setRegelmatigebestuurderPostcode($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Postcode = $par;
    }

    public function setRegelmatigebestuurderHuisnummer($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Huisnummer = $par;
    }

    public function setRegelmatigebestuurderGeslacht($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Geslacht = $par;
    }

    public function setRegelmatigebestuurderBeroep($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Beroep = $par;
    }

    public function setRegelmatigebestuurderWerkgever($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Werkgever = $par;
    }

    public function setRegelmatigebestuurderDatumknmvvro($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Datumknmvvro = $par;
    }

    public function setRegelmatigebestuurderDatumknmvvrt($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Datumknmvvrt = $par;
    }

    public function setRegelmatigebestuurderDatumnvvm($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Datumnvvm = $par;
    }

    public function setRegelmatigebestuurderDatumrijbewijs($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Datumrijbewijs = $par;
    }

    public function setRegelmatigebestuurderJarenrijbewijs($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenrijbewijs = $par;
    }

    public function setRegelmatigebestuurderJarenschadevrij($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenschadevrij = $par;
    }

    public function setRegelmatigebestuurderJarenverzekerd($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenverzekerd = $par;
    }

    public function setRegelmatigebestuurderBS_GeclaimdeschuldschadesDatum($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->BS_Geclaimdeschuldschades->Datum = $par;
    }

    public function setRegelmatigebestuurderBmverklaring($par) {
        $this->xml->Functie->Parameters->Regelmatigebestuurder->Bmverklaring = $par;
    }

    public function setVerzekeringnemerPostcode($par) {
        $this->xml->Functie->Parameters->Verzekeringnemer->Postcode = $par;
    }

    public function setVoertuigBouwdatum($par) {
        $this->xml->Functie->Parameters->Voertuig->Bouwdatum = $par;
    }

    public function setVoertuigTypeid($par) {
        $this->xml->Functie->Parameters->Voertuig->Typeid = $par;
    }

    public function setVoertuigInclbtw($par) {
        $this->xml->Functie->Parameters->Voertuig->Inclbtw = $par;
    }

    public function setVoertuigNieuwwaarde($par) {
        $this->xml->Functie->Parameters->Voertuig->Nieuwwaarde = $par;
    }

    public function setVoertuigGewicht($par) {
        $this->xml->Functie->Parameters->Voertuig->Gewicht = $par;
    }

    public function setVoertuigDagwaarde($par) {
        $this->xml->Functie->Parameters->Voertuig->Dagwaarde = $par;
    }

    public function setVoertuigCilinderinhoud($par) {
        $this->xml->Functie->Parameters->Voertuig->Cilinderinhoud = $par;
    }

    public function setVoertuigVermogen($par) {
        $this->xml->Functie->Parameters->Voertuig->Vermogen = $par;
    }

    public function setVoertuigBeveiliging($par) {
        $this->xml->Functie->Parameters->Voertuig->Beveiliging = $par;
    }

    public function setVoertuigDatatagx3($par) {
        $this->xml->Functie->Parameters->Voertuig->Datatagx3 = $par;
    }

    public function setVoertuigWaardezijspan($par) {
        $this->xml->Functie->Parameters->Voertuig->Waardezijspan = $par;
    }

    public function setVoertuigWaardeaccessoires($par) {
        $this->xml->Functie->Parameters->Voertuig->Waardeaccessoires = $par;
    }

    public function setVoertuigKenteken($par) {
        $this->xml->Functie->Parameters->Voertuig->Kenteken = $par;
    }

    public function setVoertuigEersteeigenaar($par) {
        $this->xml->Functie->Parameters->Voertuig->Eersteeigenaar = $par;
    }

    public function setHuidigepolisProductid($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Productid = $par;
    }

    public function setHuidigepolisAanvullingenAanvullingid($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Aanvullingen->Aanvullingid = $par;
    }

    public function setHuidigepolisIngangsdatum($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Ingangsdatum = $par;
    }

    public function setHuidigepolisDekking($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Dekking = $par;
    }

    public function setHuidigepolisBm($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Bm = $par;
    }

    public function setHuidigepolisBmtrede($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Bmtrede = $par;
    }

    public function setHuidigepolisAantalvrouwentreden($par) {
        $this->xml->Functie->Parameters->Huidigepolis->Aantalvrouwentreden = $par;
    }

    public function setNieuwepolisIngangsdatum($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum = $par;
    }

    public function setNieuwepolisKilometrage($par) {
        $this->xml->Functie->Parameters->Nieuwepolis->Kilometrage = $par;
    }

    public function setAutopolisProductid($par) {
        $this->xml->Functie->Parameters->Autopolis->Productid = $par;
    }

    public function setAutopolisJarenschadevrij($par) {
        $this->xml->Functie->Parameters->Autopolis->Jarenschadevrij = $par;
    }

    public function setAutopolisPA_GeclaimdeschuldschadesDatum($par) {
        $this->xml->Functie->Parameters->Autopolis->PA_Geclaimdeschuldschades->Datum = $par;
    }

    public function setProductselectieProductId($par) {
        $this->xml->Functie->Parameters->Productselectie->Product->Id = $par;
    }

    public function setPremieobjectenPremieobjectBetalingstermijn($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn = $par;
    }

    public function setPremieobjectenPremieobjectContractsduur($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Contractsduur = $par;
    }

    public function setPremieobjectenPremieobjectDekking($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Dekking = $par;
    }

    public function setPremieobjectenPremieobjectGewensteigenrisico($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico = $par;
    }

    public function setPremieobjectenPremieobjectWinterstopregeling($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Winterstopregeling = $par;
    }

    public function setPremieobjectenPremieobjectWinterstopperiode($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Winterstopperiode = $par;
    }

    public function setPremieobjectenPremieobjectPoliskostenincl($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl = $par;
    }

    public function setPremieobjectenPremieobjectAssurantiebelastingincl($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Assurantiebelastingincl = $par;
    }

    public function setPremieobjectenPremieobjectProlongatiekostenincl($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Prolongatiekostenincl = $par;
    }

    public function setPremieobjectenPremieobjectExtrasincl($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Extrasincl = $par;
    }

    public function setPremieobjectenPremieobjectPremies($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies = $par;
    }

    public function setPremieobjectenPremieobjectPO_ProductselectiePO_ProductId($par) {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id  = $par;
    }

    public function deleteRegelmatigebestuurderGeboortedatum() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Geboortedatum );
    }

    public function deleteRegelmatigebestuurderPostcode() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Postcode );
    }

    public function deleteRegelmatigebestuurderHuisnummer() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Huisnummer );
    }

    public function deleteRegelmatigebestuurderGeslacht() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Geslacht );
    }

    public function deleteRegelmatigebestuurderBeroep() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Beroep );
    }

    public function deleteRegelmatigebestuurderWerkgever() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Werkgever );
    }

    public function deleteRegelmatigebestuurderDatumknmvvro() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Datumknmvvro );
    }

    public function deleteRegelmatigebestuurderDatumknmvvrt() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Datumknmvvrt );
    }

    public function deleteRegelmatigebestuurderDatumnvvm() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Datumnvvm );
    }

    public function deleteRegelmatigebestuurderDatumrijbewijs() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Datumrijbewijs );
    }

    public function deleteRegelmatigebestuurderJarenrijbewijs() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenrijbewijs );
    }

    public function deleteRegelmatigebestuurderJarenschadevrij() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenschadevrij );
    }

    public function deleteRegelmatigebestuurderJarenverzekerd() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Jarenverzekerd );
    }

    public function deleteRegelmatigebestuurderBS_GeclaimdeschuldschadesDatum() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->BS_Geclaimdeschuldschades->Datum );
    }

    public function deleteRegelmatigebestuurderBmverklaring() {
        unset($this->xml->Functie->Parameters->Regelmatigebestuurder->Bmverklaring );
    }

    public function deleteVerzekeringnemerPostcode() {
        unset($this->xml->Functie->Parameters->Verzekeringnemer->Postcode );
    }

    public function deleteVoertuigBouwdatum() {
        unset($this->xml->Functie->Parameters->Voertuig->Bouwdatum );
    }

    public function deleteVoertuigTypeid() {
        unset($this->xml->Functie->Parameters->Voertuig->Typeid );
    }

    public function deleteVoertuigInclbtw() {
        unset($this->xml->Functie->Parameters->Voertuig->Inclbtw );
    }

    public function deleteVoertuigNieuwwaarde() {
        unset($this->xml->Functie->Parameters->Voertuig->Nieuwwaarde );
    }

    public function deleteVoertuigGewicht() {
        unset($this->xml->Functie->Parameters->Voertuig->Gewicht );
    }

    public function deleteVoertuigDagwaarde() {
        unset($this->xml->Functie->Parameters->Voertuig->Dagwaarde );
    }

    public function deleteVoertuigCilinderinhoud() {
        unset($this->xml->Functie->Parameters->Voertuig->Cilinderinhoud );
    }

    public function deleteVoertuigVermogen() {
        unset($this->xml->Functie->Parameters->Voertuig->Vermogen );
    }

    public function deleteVoertuigBeveiliging() {
        unset($this->xml->Functie->Parameters->Voertuig->Beveiliging );
    }

    public function deleteVoertuigDatatagx3() {
        unset($this->xml->Functie->Parameters->Voertuig->Datatagx3 );
    }

    public function deleteVoertuigWaardezijspan() {
        unset($this->xml->Functie->Parameters->Voertuig->Waardezijspan );
    }

    public function deleteVoertuigWaardeaccessoires() {
        unset($this->xml->Functie->Parameters->Voertuig->Waardeaccessoires );
    }

    public function deleteVoertuigKenteken() {
        unset($this->xml->Functie->Parameters->Voertuig->Kenteken );
    }

    public function deleteVoertuigEersteeigenaar() {
        unset($this->xml->Functie->Parameters->Voertuig->Eersteeigenaar );
    }

    public function deleteHuidigepolisProductid() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Productid );
    }

    public function deleteHuidigepolisAanvullingenAanvullingid() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Aanvullingen->Aanvullingid );
    }

    public function deleteHuidigepolisIngangsdatum() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Ingangsdatum );
    }

    public function deleteHuidigepolisDekking() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Dekking );
    }

    public function deleteHuidigepolisBm() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Bm );
    }

    public function deleteHuidigepolisBmtrede() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Bmtrede );
    }

    public function deleteHuidigepolisAantalvrouwentreden() {
        unset($this->xml->Functie->Parameters->Huidigepolis->Aantalvrouwentreden );
    }

    public function deleteNieuwepolisIngangsdatum() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Ingangsdatum );
    }

    public function deleteNieuwepolisKilometrage() {
        unset($this->xml->Functie->Parameters->Nieuwepolis->Kilometrage );
    }

    public function deleteAutopolisProductid() {
        unset($this->xml->Functie->Parameters->Autopolis->Productid );
    }

    public function deleteAutopolisJarenschadevrij() {
        unset($this->xml->Functie->Parameters->Autopolis->Jarenschadevrij );
    }

    public function deleteAutopolisPA_GeclaimdeschuldschadesDatum() {
        unset($this->xml->Functie->Parameters->Autopolis->PA_Geclaimdeschuldschades->Datum );
    }

    public function deleteProductselectieProductId() {
        unset($this->xml->Functie->Parameters->Productselectie->Product->Id );
    }

    public function deletePremieobjectenPremieobjectBetalingstermijn() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Betalingstermijn );
    }

    public function deletePremieobjectenPremieobjectContractsduur() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Contractsduur );
    }

    public function deletePremieobjectenPremieobjectDekking() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Dekking );
    }

    public function deletePremieobjectenPremieobjectGewensteigenrisico() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Gewensteigenrisico );
    }

    public function deletePremieobjectenPremieobjectWinterstopregeling() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Winterstopregeling );
    }

    public function deletePremieobjectenPremieobjectWinterstopperiode() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Winterstopperiode );
    }

    public function deletePremieobjectenPremieobjectPoliskostenincl() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Poliskostenincl );
    }

    public function deletePremieobjectenPremieobjectAssurantiebelastingincl() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Assurantiebelastingincl );
    }

    public function deletePremieobjectenPremieobjectProlongatiekostenincl() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Prolongatiekostenincl );
    }

    public function deletePremieobjectenPremieobjectExtrasincl() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Extrasincl );
    }

    public function deletePremieobjectenPremieobjectPremies() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->Premies );
    }

    public function deletePremieobjectenPremieobjectPO_ProductselectiePO_ProductId() {
        unset($this->xml->Functie->Parameters->Premieobjecten->Premieobject->PO_Productselectie->PO_Product->Id );
    }


    public function setPremieobjectenPremieobjectIncludeproductenzonderacceptatie( $par )
    {
        $this->xml->Functie->Parameters->Premieobjecten->Premieobject->Includeproductenzonderacceptatie = $par;
    }







}