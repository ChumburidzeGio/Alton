<?php

namespace App\Resources\Blaudirekt\Requests;
use App\Interfaces\ResourceInterface;
use App\Resources\Blaudirekt\BlaudirektAbstractRequest;

class PremiumRequest extends BlaudirektAbstractRequest
{
    protected $insuranceName;

    protected $httpMethod = self::METHOD_POST;

    protected $inputTransformations = [
        ResourceInterface::BIRTHDATE => 'convertDate',
        ResourceInterface::RANGE => 'convertRange',
    ];

    protected $httpBodyEncoding = self::DATA_ENCODING_JSON;

    protected $inputToExternalMapping = [
        ResourceInterface::PRODUCT_ID => 'products',
        ResourceInterface::BIRTHDATE => 'data.Vertrag_Kunde_geburtsdatum', //933465600
        ResourceInterface::RANGE => 'data.Vertrag_personenkreis_form', //single|familie|partner|alleinerziehende
        ResourceInterface::TARIFF_TYPE => 'data.Vertrag_tarifgruppe_form', //0
        ResourceInterface::PREINSURANCE_CONTRACT => 'data.Vertrag_vn_vorversicherung_form', //1
        ResourceInterface::RECENT_DAMAGE => 'data.Vertrag_schadensfrei_form', //0
    ];

    protected $externalToResultMapping = [
        'id' => ResourceInterface::PRODUCT_ID,
        'product' => ResourceInterface::TITLE,
        'cover_more' => ResourceInterface::COVERAGE,
        'Vertrag_selbstbehalt' => ResourceInterface::CONTRACT_DEDUCTIBLE,
        'Vertrag_beitrag_brutto' => ResourceInterface::PREMIUM_GROSS,
        'Vertrag_ds' => ResourceInterface::COVERAGE_SUM,
        'Vertrag_ds_vermoegen' => ResourceInterface::COVERAGE_SUM_ASSETS,
        'Vertrag_ds_mietsach' => ResourceInterface::COVERAGE_SUM_RENTED_GOODS,
        'Vertrag_sb_mietsachschaeden_gebaeude' => ResourceInterface::RENTAL_BUILDING_DAMAGE_OWNRISK,
        'Vertrag_schaeden_durch_nicht_deliktfaehige_kinder' => ResourceInterface::DAMAGES_BY_CHILD,
        'Vertrag_pers_schaeden_durch_nicht_deliktfaehige_kinder' => ResourceInterface::PERSONAL_DAMAGE_BY_CHILD,
        'Vertrag_sb_schaeden_durch_nicht_deliktfaehige_kinder' => ResourceInterface::OWNRISK_DAMAGE_BY_CHILD,
        'Vertrag_forderungsausfalldeckung_sb' => ResourceInterface::OWNRISK_LOSS_OFF_DEBT_INCOME_COVERAGE,
        'Vertrag_forderungsausfallversicherung' => ResourceInterface::LOSS_OFF_DEBT_INCOME_INSURANCE,
        'Vertrag_rechtsschutz_forderungsausfall' => ResourceInterface::LEGAL_PROTECTION_LOSS_OFF_DEBT_INCOME,
        'Vertrag_privater_schluesselverlust' => ResourceInterface::PRIVATE_LOSS_OFF_KEYS,
        'Vertrag_sb_privater_schluesselverlust' => ResourceInterface::OWNRISK_PRIVATE_LOSS_OFF_KEYS,
        'Vertrag_dienstlicher_schluesselverlust' => ResourceInterface::BUSINESS_LOSS_OFF_KEYS,
        'Vertrag_sb_dienstlicher_schluesselverlust' => ResourceInterface::OWNRISK_LOSS_OFF_KEYS,
        'Vertrag_fremdhuetung_hunde' => ResourceInterface::EXTERNAL_DOG_CARETAKER,
        'Vertrag_schaeden_durch_hueten_fremder_pferde' => ResourceInterface::DAMAGES_FROM_THIRDPARTY_HORSE_CARING,
        'Vertrag_alleinstehendes_elternteil' => ResourceInterface::SINGLE_PARENT,
        'Vertrag_mitversicherung_eines_au_pair' => ResourceInterface::COINSURED_AUPAIR,
        'Vertrag_schaeden_bei_der_taetigkeit_als_tagesmutter' => ResourceInterface::DAMAGES_FROM_CONDUCTING_NANNY_ACTIVITIES,
        'Vertrag_ehrenamtliche_taetigkeiten' => ResourceInterface::VOLUNTARY_WORK,
        'Vertrag_schaeden_durch_modellfahrzeuge' => ResourceInterface::DAMAGES_FROM_MODEL_VEHICLES,
        'Vertrag_fahrraeder_besitz_gebrauch' => ResourceInterface::BICYCLE_OWNING_USAGE,
        'Vertrag_einfamilienhaus_selbstgenutzt' => ResourceInterface::ONE_FAMILY_HOUSE_OCCUPIED_BY_OWNER,
        'Vertrag_vermieten_einer_wohnung_im_bewohnten_haus' => ResourceInterface::LEASING_APARTMENT_IN_INHABITED_HOUSE,
        'Vertrag_ferienwohnung_selbstgenutzt' => ResourceInterface::VACATION_APARTMENT_OCCUPIED_BY_OWNER,
        'Vertrag_bauvorhaben_bis_150k_euro' => ResourceInterface::BUILDING_PROJECTS_UNTIL_150000_EUROS,
        'Vertrag_geltungsbereich_europa' => ResourceInterface::INSURANCE_SCOPE_EUROPE,
        'Vertrag_geltungsbereich_welt' => ResourceInterface::INSURANCE_SCOPE_WORLD,
        'Vertrag_haeusliche_abwaesser_1' => ResourceInterface::DOMESTIC_DRAINWATER_1,
        'Vertrag_allmaehlichkeitsschaeden' => ResourceInterface::GRADUAL_DAMAGES,
        'Vertrag_oel_oberirdisch' => ResourceInterface::OIL_ABOVE_GROUND,
        'Vertrag_oel_unterirdisch' => ResourceInterface::OIL_BENEATH_GROUND,
        'Vertrag_schaeden_an_gemieteten_sachen_1k' => ResourceInterface::DAMAGES_TO_LEASED_PROPERTY_UNTIL_1000_EUROS,
        'Vertrag_sb_gemietet' => ResourceInterface::OWNRISK_LEASED_PROPERTY,
        'Vertrag_gefaelligkeitsschaeden_sb100' => ResourceInterface::DAMAGE_FROM_COMPLACENCY_OWNRISK_100EUROS,
        'Vertrag_sb_gefaelligkeitsschaeden' => ResourceInterface::OWNRISK_DAMAGE_FROM_COMPLACENCY,
    ];

    public function __construct()
    {
        parent::__construct("service/bd/{$this->insuranceName}/calculate");
    }

    public function getResult()
    {
        $premiums = parent::getResult();

        return $premiums;
    }

    public function setParams(array $params)
    {
        parent::setParams($params);

        if (!is_array($this->params['products'])) {
            $this->params['products'] = [$this->params['products']];
        }
    }

    public function executeFunction()
    {
        parent::executeFunction();

        $this->result = array_filter($this->result, function($item) {
            return $item['anzeige'] == true;
        });
    }

}