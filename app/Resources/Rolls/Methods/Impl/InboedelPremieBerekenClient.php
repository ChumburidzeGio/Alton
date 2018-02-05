<?php namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;

class InboedelPremieBerekenClient extends RollsAbstractSoapRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;

    public function __construct()
    {
        parent::__construct();
        $this->init(((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.premie_inboedel_function')));
        // TODO: Get officeId from user/db/config
        $this->officeId = 8824;
    }

    public function setParams(Array $params)
    {
        if(isset($params[ResourceInterface::SELECTED_COVERAGES])){
            $this->selectedCoverages = is_array($params[ResourceInterface::SELECTED_COVERAGES]) ? $params[ResourceInterface::SELECTED_COVERAGES] : array_filter(explode(',', (string) $params[ResourceInterface::SELECTED_COVERAGES]));
        }

        $paramNode = $this->xml->Functie->Parameters;

        // Verzekeringnemer
        $paramNode->Verzekeringnemer->Geboortedatum       = str_replace('-', '', trim($params[ResourceInterface::BIRTHDATE]));
        $paramNode->Verzekeringnemer->Gezinssamenstelling = $params[ResourceInterface::FAMILY_COMPOSITION];
        unset($paramNode->Verzekeringnemer->Aantalkinderen);
        $paramNode->Verzekeringnemer->Nettomaandinkomenhuishouden = $params[ResourceInterface::MONTHLY_NET_INCOME];

        // Hoofdkostwinner
        $paramNode->Hoofdkostwinner->Geboortedatum     = str_replace('-', '', trim($params[ResourceInterface::BIRTHDATE]));
        $paramNode->Hoofdkostwinner->Nettomaandinkomen = $params[ResourceInterface::MONTHLY_NET_INCOME];

        // Objectinboedel
        $paramNode->Objectinboedel->Postcode   = $params[ResourceInterface::POSTAL_CODE];
        $paramNode->Objectinboedel->Huisnummer = $params[ResourceInterface::HOUSE_NUMBER];
        unset($paramNode->Objectinboedel->Huisnummertoevoeging);
        $paramNode->Objectinboedel->Eigenaar = $this->rollsBool('true');
        // Home Type & Construction
        $paramNode->Objectinboedel->Bouwaardmuren                     = $params[ResourceInterface::HOUSE_WALL_MATERIAL];
        $paramNode->Objectinboedel->Bouwaarddak                       = $params[ResourceInterface::HOUSE_ROOF_MATERIAL];
        $paramNode->Objectinboedel->Soortwoning                       = $params[ResourceInterface::HOUSE_TYPE];
        $paramNode->Objectinboedel->Belending                         = $params[ResourceInterface::HOUSE_ABUTMENT];
        $paramNode->Objectinboedel->Bestemming                        = $params[ResourceInterface::HOUSE_USAGE];
        $paramNode->Objectinboedel->Bouwjaar                          = $params[ResourceInterface::CONSTRUCTION_DATE_YEAR];
        $paramNode->Objectinboedel->Aantalslaaphobbystudeerwerkkamers = $params[ResourceInterface::SLEEPHOBBYSTUDYWORK_ROOM_COUNT];
        // Alarm
        $paramNode->Objectinboedel->Inbraakpreventie->Elektronischinbraakalarm = $this->rollsBool($params[ResourceInterface::HOUSE_ALARM]);
        $paramNode->Objectinboedel->Inbraakpreventie->Borgcertificaat          = $this->rollsBool($params[ResourceInterface::HOUSE_ALARM]);
        $paramNode->Objectinboedel->Inbraakpreventie->Politiekeurmerk          = $this->rollsBool($params[ResourceInterface::POLICE_MARK]);
        if(isset($params[ResourceInterface::USE_CONTENTS_VALUE_MEASUREMENT]) && $params[ResourceInterface::USE_CONTENTS_VALUE_MEASUREMENT]){
            /*
            * woz_waardeindicatie
            *
            */
            $paramNode->Objectinboedel->Inboedelwaardemeter->Aantalwooneetkamersenserres          = array_get($params,ResourceInterface::SLEEPHOBBYSTUDYWORK_ROOM_COUNT, 0 );
            $paramNode->Objectinboedel->Inboedelwaardemeter->Aantalgarages                        = 0;
            $paramNode->Objectinboedel->Inboedelwaardemeter->Aantalschuren                        = 0;
            $paramNode->Objectinboedel->Inboedelwaardemeter->Wozwaarde                            = array_get($params,ResourceInterface::VALUATION_OF_REAL_ESTATE, 0 );
            $paramNode->Objectinboedel->Inboedelwaardemeter->Herbouwwaarde                        = array_get($params,ResourceInterface::HOUSE_REBUILD, 0 );
            $paramNode->Objectinboedel->Inboedelwaardemeter->Totalewooninhoud                     = array_get($params,ResourceInterface::HOUSE_VOLUME_SOURCE, 0 );
            $paramNode->Objectinboedel->Inboedelwaardemeter->Totalewoonoppervlakte                = array_get($params,ResourceInterface::LIVING_AREA_TOTAL, 0 );
            $paramNode->Objectinboedel->Inboedelwaardemeter->Totaleoppervlaktewoonkamersenkeukens = array_get($params,ResourceInterface::SURFACE_AREA_MAIN_BUILDING, 0 );
            $paramNode->Objectinboedel->Inboedelwaardemeter->Oppervlaktebeganegrond               = array_get($params,ResourceInterface::SURFACE_AREA_MAIN_BUILDING, 0 );
            $paramNode->Objectinboedel->Inboedelwaardemeter->Aantalwoonlagen                      = array_get($params,ResourceInterface::NUMBER_OF_FLOORS, 0 );

            //
            $paramNode->Objectinboedel->Waardeinboedeleigenschattingtaxatie = 0 ;

            //afkomstig van meter
            $paramNode->Objectinboedel->Inboedelwaardeafkomstigvan = 2;
        }else{
            unset($paramNode->Objectinboedel->Inboedelwaardemeter);
            $paramNode->Objectinboedel->Waardeinboedeleigenschattingtaxatie = $params[ResourceInterface::CONTENTS_ESTIMATE];
        }
        // Waarde afkomstig van schatting

        $paramNode->Objectinboedel->Huurderseigenaarsbelang    = 0;
        $paramNode->Objectinboedel->Bouwjaarrietendak          = '';
        $paramNode->Objectinboedel->Rietendakgeimprigneerd     = '';
        $paramNode->Objectinboedel->Skandinavischebouwaard     = '';
        unset($paramNode->Objectinboedel->Verdiepingsvloer);
        unset($paramNode->Objectinboedel->Geslotenconstructieonderrietendak);

        // Nieuwepolis
        $paramNode->Nieuwepolis->Ingangsdatum = $this->getNow();
        unset($paramNode->Nieuwepolis->Minimaledekking);
        unset($paramNode->Nieuwepolis->Aanvullingsoorten);
        unset($paramNode->Nieuwepolis->NP_Rubrieken);
        unset($paramNode->Nieuwepolis->Status);

        // Huidigepolis
        unset($paramNode->Huidigepolis);

        // Productselectie
        unset($paramNode->Productselectie);

        // Premieobjecten
        $paramNode->Premieobjecten->Premieobject->Betalingstermijn                 = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.termijn_maand'));
        $paramNode->Premieobjecten->Premieobject->Gewensteigenrisico               = $params[ResourceInterface::CALCULATION_OWN_RISK];
        $paramNode->Premieobjecten->Premieobject->Includeproductenzonderacceptatie = $this->rollsBool('true');
        $paramNode->Premieobjecten->Premieobject->Assurantiebelastingincl          = $this->rollsBool('true');
        unset($paramNode->Premieobjecten->Premieobject->Poliskostenincl);

        if(isset($params[ResourceInterface::USE_CONTENTS_VALUE_MEASUREMENT]) && $params[ResourceInterface::USE_CONTENTS_VALUE_MEASUREMENT]){
            $paramNode->Premieobjecten->Premieobject->Rekenenmetwaardemeter = 'ja';
        } else {
            unset($paramNode->Premieobjecten->Premieobject->Rekenenmetwaardemeter);
        }

        unset($paramNode->Premieobjecten->Premieobject->Contractsduur);
        unset($paramNode->Premieobjecten->Premieobject->VerzekerdeSom_Buitenhuisdekking);
        unset($paramNode->Premieobjecten->Premieobject->Premies);
        unset($paramNode->Premieobjecten->Premieobject->Combinaties);
        unset($paramNode->Premieobjecten->Premieobject->PO_Productselectie);

        // Only specific products
        if(isset($params[ResourceInterface::RESOURCE][ResourceInterface::ID])){
            $this->addProductIdsFilter($params[ResourceInterface::RESOURCE][ResourceInterface::ID]);
        }
    }

    public function getResult()
    {
        $fullResult = parent::getResult();
        $result     = $this->extractResult('Premies', 'Premie', $fullResult->Premieobjecten->Premieobject);
        return $this->processAdditionalCoverages($result);
    }
}