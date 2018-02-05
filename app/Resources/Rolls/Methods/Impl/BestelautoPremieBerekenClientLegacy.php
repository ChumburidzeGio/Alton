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


class BestelautoPremieBerekenClientLegacy extends RollsAbstractSoapRequest
{

    protected $cacheDays = 1;

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
            'example' => '06-VRJ-2',
            'filter'  => 'filterAlfaNumber'
        ],
        ResourceInterface::CONSTRUCTION_DATE    => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '2009-04-01',
            'filter'  => 'filterNumber'
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
        $this->init( Config::get( 'resource_rolls.functions.premie_bestelauto_function_legacy' ) );
    }

    public function getResult()
    {
        return $this->getMotorizedPremieResult('van_option_list');
    }


    public function setParams( Array $params )
    {
        //        //old working code do not fucking touch

        if (isset( $params[ResourceInterface::LICENSEPLATE] )) {
            $res           = $this->internalRequest('vaninsurance','licenseplate', [ ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE]]);
            $bouwdatum = isset( $params[ResourceInterface::CONSTRUCTION_DATE] ) ? $params[ResourceInterface::CONSTRUCTION_DATE] : $res[ResourceInterface::CONSTRUCTION_DATE];
            $bouwdatum = ResourceFilterHelper::filterNumber($bouwdatum);
            $type      = isset( $params[ResourceInterface::TYPE_ID] ) ? $params[ResourceInterface::TYPE_ID] : $res[ResourceInterface::TYPES][0][ResourceInterface::RESOURCE_ID] ;
        } else {
            if ( ! ( isset( $params[ResourceInterface::TYPE_ID] ) && isset( $params[ResourceInterface::CONSTRUCTION_DATE] ) )) {
                $this->setErrorString( 'Either licenseplate or (type_id and construction_data) is required for calculation' );
                return;
            }
            $bouwdatum = $params[ResourceInterface::CONSTRUCTION_DATE];
            $type      = $params[ResourceInterface::TYPE_ID];

        }



        $geboortedatum     = $params[ResourceInterface::BIRTHDATE] ;
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
        $particuliergebruik = Config::get( 'resource_rolls.options.optie_ja' );
        $betalingstermijn   = Config::get( 'resource_rolls.options.termijn_maand' );

        //delete overbodige parameters
        $this->deleteParameterTree( 'Huidigepolis' );
        $this->deleteParameterTree( 'Productselectie' );
        $this->deleteParameterTree( 'Motorpolis' );
        $this->deleteParameterTree( 'Verzekeringnemer' );


        //format YYYYMMDD
        $this->setRegelmatigebestuurderGeboortedatum( $geboortedatum );

        //format ####CC
        $this->setRegelmatigebestuurderPostcode( $postcode );

        //deletes
        $this->deleteRegelmatigebestuurderGeslacht();
        $this->deleteRegelmatigebestuurderBmverklaring();

        $this->setNieuwepolisInclbtw( Config::get( 'resource_rolls.options.optie_ja' ) );
        $this->deleteNieuwepolisVervoervan();
        $this->deleteNieuwepolisBpmterugvorderbaar();
        $this->deletePremieobjectenPremieobjectPoliskostenincl();
        $this->deletePremieobjectenPremieobjectAssurantiebelastingincl();
        $this->deletePremieobjectenProductselectie();


        //format 'man' of 'vrouw'
        $this->setRegelmatigebestuurderJarenrijbewijs( $jarenrijbewijs );
        $this->setRegelmatigebestuurderJarenschadevrij( $schadevrij );

        //verwijder alle standaar geclaimde schuld schade nodes
        $this->deleteRegelmatigeBestuurderGeclaimdeSchuldSchade();
        $this->setRegelmatigebestuurderGeboortedatum( $geboortedatum );
        $this->setRegelmatigebestuurderJarenverzekerd( $jarenverzekerd );
        //voertuig type id
        $this->setVoertuigTypeid( $type );
        //nieuwwaarde incl btw
        $this->setVoertuigInclbtw( Config::get( 'resource_rolls.options.optie_ja' ) );


        $this->deleteVoertuigVermogen();
        $this->deleteVoertuigBrandstof();
        $this->deleteVoertuigNieuwwaarde();
        $this->deleteVoertuigDagwaarde();


        if(isset($params[ResourceInterface::LICENSEPLATE])){
            $this->setVoertuigKenteken($params[ResourceInterface::LICENSEPLATE]);
        }else{
            $this->deleteVoertuigKenteken();
        }



        //$this->setVoertuigGewicht($gewicht);
        $this->setVoertuigBeveiliging( $beveiliging );
        $this->setVoertuigBouwdatum( $bouwdatum );
        //set polist ingangs datum op vandaag
        $this->setNieuwepolisIngangsdatum( $ingangsdatum );
        //zet op particulier gebruik
        $this->setNieuwepolisParticuliergebruik( $particuliergebruik );
        //kilometer stand
        $this->setNieuwepolisKilometrage( $kilometerperjaar );
        //
        $this->deleteNieuwepolisNPRebrieken();

        //premie object
        $this->setPremieobjectenPremieobjectBetalingstermijn( $betalingstermijn );
        $this->setPremieobjectenPremieobjectDekking( $dekking );
        $this->deletePremieobjectenPremieobjectGewensteigenrisico();
        $this->setPremieobjectenPremieobjectGewensteigenrisico( $eigenrisico ); // Was standaard 150

        $this->setPremieobjectenPremieobjectIncludeproductenzonderacceptatie($this->rollsBool('false'));

        if (is_array( $IDS ) && sizeof( $IDS ) > 1) {
            $this->addPremieobjecteProductselectieArray( $IDS );
        }
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