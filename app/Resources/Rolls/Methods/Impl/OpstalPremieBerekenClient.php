<?php namespace App\Resources\Rolls\Methods\Impl;

use App\Interfaces\ResourceInterface;
use App\Resources\Rolls\Methods\RollsAbstractSoapRequest;
use Config;


class OpstalPremieBerekenClient extends RollsAbstractSoapRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;

    public function __construct()
    {
        parent::__construct();
        $this->init(((app()->configure('resource_rolls')) ? '' : config('resource_rolls.functions.premie_opstal_function')));

        // Set office ID to Lancyr Kantoorid
        // TODO: Let this be determined by user/website
        $this->officeId = 8824;
    }

    public function setParams( Array $params )
    {
        if (isset($params[ResourceInterface::SELECTED_COVERAGES]))
            $this->selectedCoverages = is_array($params[ResourceInterface::SELECTED_COVERAGES]) ? $params[ResourceInterface::SELECTED_COVERAGES] : array_filter(explode(',', (string)$params[ResourceInterface::SELECTED_COVERAGES]));

        $paramNode = $this->xml->Functie->Parameters;

        // Verzekeringnemer
        $paramNode->Verzekeringnemer->Geboortedatum = str_replace( '-', '', trim(array_get($params, ResourceInterface::BIRTHDATE)));
        $paramNode->Verzekeringnemer->Gezinssamenstelling = array_get($params, ResourceInterface::FAMILY_COMPOSITION);

        // Hoofdkostwinner
        $paramNode->Hoofdkostwinner->Nettomaandinkomen = array_get($params, ResourceInterface::MONTHLY_NET_INCOME);

        // Objectopstal (House location, type, construction)
        $paramNode->Objectopstal->Postcode = array_get($params, ResourceInterface::POSTAL_CODE);
        $paramNode->Objectopstal->Huisnummer = array_get($params, ResourceInterface::HOUSE_NUMBER);
        unset($paramNode->Objectopstal->Huisnummertoevoeging);
        // Home Type & Construction
        $paramNode->Objectopstal->Bouwjaar = array_get($params, ResourceInterface::CONSTRUCTION_DATE_YEAR);
        $paramNode->Objectopstal->Nieuwbouw = $this->rollsBool(array_get($params, ResourceInterface::HOUSE_IS_NEWLY_BUILT));
        $paramNode->Objectopstal->Eigenaar = $this->rollsBool('true');
        $paramNode->Objectopstal->Bouwaardmuren = array_get($params, ResourceInterface::HOUSE_WALL_MATERIAL);
        $paramNode->Objectopstal->Skandinavischebouwaard = '';
        $paramNode->Objectopstal->Bouwaarddak = array_get($params, ResourceInterface::HOUSE_ROOF_MATERIAL);
        $paramNode->Objectopstal->Geslotenconstructieonderrietendak = '';
        $paramNode->Objectopstal->Bouwjaarrietendak = '';
        $paramNode->Objectopstal->Rietendakgeimprigneerd = '';
        $paramNode->Objectopstal->Verdiepingsvloer = array_get($params, ResourceInterface::HOUSE_ABOVEGROUND_FLOOR_MATERIAL);
        $paramNode->Objectopstal->Beganegrondvloer = array_get($params, ResourceInterface::HOUSE_GROUND_FLOOR_MATERIAL);
        $paramNode->Objectopstal->Soortwoning = array_get($params, ResourceInterface::HOUSE_TYPE);
        $paramNode->Objectopstal->Belending = array_get($params, ResourceInterface::HOUSE_ABUTMENT);
        $paramNode->Objectopstal->Bestemming = array_get($params, ResourceInterface::HOUSE_USAGE);
        $paramNode->Objectopstal->Monument = $this->rollsBool(array_get($params, ResourceInterface::HOUSE_IS_MONUMENT));
        $paramNode->Objectopstal->Herbouwwaarde = array_get($params, ResourceInterface::HOUSE_REBUILD);
        $paramNode->Objectopstal->Herbouwwaardeafkomstigvan = '';
        unset($paramNode->Objectopstal->Herbouwwaardemeter);
        $paramNode->Objectopstal->Totalewoonoppervlakte = array_get($params, ResourceInterface::LIVING_AREA_TOTAL);
        $paramNode->Objectopstal->Perceeloppervlakte = array_get($params, ResourceInterface::PARCEL_SIZE);
        $paramNode->Objectopstal->Aantalslaaphobbykamers = array_get($params, ResourceInterface::SLEEPHOBBYSTUDYWORK_ROOM_COUNT);
        if (isset($params[ResourceInterface::SOLARPANELS_VALUE]))
            $paramNode->Objectopstal->Waardezonnepanelen = array_get($params, ResourceInterface::SOLARPANELS_VALUE);
        else
            unset($paramNode->Objectopstal->Waardezonnepanelen);

        // Huidigepolis
        unset($paramNode->Huidigepolis);

        // Nieuwepolis
        $paramNode->Nieuwepolis->Ingangsdatum = $this->getNow();
        unset($paramNode->Nieuwepolis->Minimaledekking);
        unset($paramNode->Nieuwepolis->Aanvullingsoorten);
        unset($paramNode->Nieuwepolis->NP_Rubrieken);
        unset($paramNode->Nieuwepolis->Status);

        // Productselectie
        unset($paramNode->Productselectie);

        // Premieobjecten
        $paramNode->Premieobjecten->Premieobject->Betalingstermijn = ((app()->configure('resource_rolls')) ? '' : config('resource_rolls.options.termijn_maand'));
        $paramNode->Premieobjecten->Premieobject->Gewensteigenrisico = array_get($params, ResourceInterface::CALCULATION_OWN_RISK);
        $paramNode->Premieobjecten->Premieobject->Includeproductenzonderacceptatie = $this->rollsBool('true');
        unset($paramNode->Premieobjecten->Premieobject->Contractsduur);
        $paramNode->Premieobjecten->Premieobject->Assurantiebelastingincl = $this->rollsBool('true');
        unset($paramNode->Premieobjecten->Premieobject->VerzekerdeSom_Buitenhuisdekking);
        unset($paramNode->Premieobjecten->Premieobject->Poliskostenincl);
        unset($paramNode->Premieobjecten->Premieobject->Rekenenmetwaardemeter);
        unset($paramNode->Premieobjecten->Premieobject->Combinaties);
        unset($paramNode->Premieobjecten->Premieobject->Premies);
        unset($paramNode->Premieobjecten->Premieobject->PO_Productselectie);

        // Only specific resources
        if (isset($params[ResourceInterface::RESOURCE][ResourceInterface::ID]))
            $this->addProductIdsFilter($params[ResourceInterface::RESOURCE][ResourceInterface::ID]);
    }

    public function getResult()
    {
        $fullResult = parent::getResult();
        $result = $this->extractResult('Premies', 'Premie', $fullResult->Premieobjecten->Premieobject);
        return $this->processAdditionalCoverages($result);
    }
}