<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:39
 *
 */

namespace App\Resources\Easyswitch\Methods\Impl;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Easyswitch\Methods\EasyswitchAbstractRequest;

class EnergyDetails extends EasyswitchAbstractRequest
{

    protected $cacheDays = false;

    protected $arguments = [
        ResourceInterface::POSTAL_CODE              => [
            'rules'   => self::VALIDATION_REQUIRED_POSTAL_CODE,
            'example' => '8014EH',
            'filter'  => 'filterToUppercase'
        ],
        ResourceInterface::HOUSE_NUMBER             => [
            'rules'   => 'required | integer',
            'example' => '21'
        ],
        ResourceInterface::HOUSE_NUMBER_SUFFIX      => [
            'rules'   => 'string',
            'example' => '1E'
        ],
        ResourceInterface::CONTRACT_DURATION_MONHTS => [
            'rules'   => 'in:no_preference,continuously,12,24,36,48,60',
            'example' => 'fixed',
            'default' => 'no_preference'
        ],
        ResourceInterface::TARIFF_TYPE              => [
            'rules'   => 'in:fixed,variable,no_preference',
            'example' => 'fixed',
            'default' => 'no_preference'
        ],
        ResourceInterface::CURRENT_PROVIDER         => [
            'rules'   => 'integer',
            'example' => '1',
            'default' => '-1'
        ],
        ResourceInterface::ENERGY_TYPE              => [
            'rules'   => 'in:green-green,green-grey,grey-grey,no_preference',
            'example' => 'green',
            'default' => 'no_preference'
        ],
        ResourceInterface::ELECTRICITY_USAGE_HIGH   => [
            'rules'   => 'required | integer',
            'example' => '5400',
        ],
        ResourceInterface::ELECTRICITY_USAGE_LOW    => [
            'rules'   => 'integer',
            'example' => '5400, only needed when double meter',
            'default' => '0'
        ],
        ResourceInterface::GAS_USAGE                => [
            'rules'   => 'integer | required',
            'example' => '5400',
        ],
        ResourceInterface::BUSINESS                 => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 'false'
        ],
        ResourceInterface::CONTRACT_ID              => [
            'rules'   => 'integer | required',
            'example' => '13',
        ],
        ResourceInterface::DUMP_FIELDS              => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'default' => 0
        ],


    ];


    public function __construct()
    {
        parent::__construct('/producten/');
        $this->strictStandardFields = false;
    }


    public function getResult()
    {
        //        $arr = [];
        //        foreach($this->params as $key=>$val) {
        //            $arr[] = $key.'='.$val;
        //        }
        //        dd(implode('&',$arr));
        //dump funciton
        if($this->params[ResourceInterface::DUMP_FIELDS] == 1){
            return parent::getResult()[0];
        }

        $result                                          = parent::getResult()[0];
        $newRes                                          = [];
        $aanbieder                                       = $result['aanbieder']['generic'];
        $newRes[ResourceInterface::PROVIDER_NAME]        = $aanbieder['name'];
        $newRes[ResourceInterface::PROVIDER_PHONE]       = $aanbieder['telephone'];
        $newRes[ResourceInterface::PROVIDER_PHONE_COSTS] = $aanbieder['helpdesk_costs'];
        $newRes[ResourceInterface::PROVIDER_EMAIL]       = $aanbieder['email'];
        $newRes[ResourceInterface::PROVIDER_WEBSITE]     = $aanbieder['website'];

        $aanbiederExtra                                       = $result['aanbieder']['energy_extrainfo'];
        $newRes[ResourceInterface::PROVIDER_ACCEPTGIRO_COSTS] = ($aanbiederExtra['acceptgiro_kosten'] != 'Nee') ? $aanbiederExtra['kosten_eerste'] : 'Nee';
        $newRes[ResourceInterface::PROVIDER_LICENSEE]         = $aanbiederExtra['vergunninghouder'];


        $newRes[ResourceInterface::PROVIDER_STREET]       = $aanbieder['street_name'];
        $newRes[ResourceInterface::PROVIDER_HOUSE_NUMBER] = $aanbieder['street_number'];
        $newRes[ResourceInterface::PROVIDER_SUFFIX]       = $aanbieder['street_extension'];
        $newRes[ResourceInterface::PROVIDER_POSTAL_CODE]  = $aanbieder['zipcode'];
        $newRes[ResourceInterface::PROVIDER_CITY]         = $aanbieder['postal_city'];

        //hardcoded
        $newRes[ResourceInterface::CONTRACT_CONDITIONS][] = [ResourceInterface::LABEL => 'algemene-voorwaarden.pdf', ResourceInterface::NAME => '//www.easyswitch.nl/images/algemene-voorwaarden.pdf'];
        if(isset($result['voorwaarden'])){
            foreach($result['voorwaarden'] as $voorwaarde){
                $condition[ResourceInterface::LABEL]              = $voorwaarde['name'];
                $condition[ResourceInterface::NAME]               = $voorwaarde['url'];
                $newRes[ResourceInterface::PROVIDER_CONDITIONS][] = $condition;
            }
        }


        if(isset($result['prijs']['prijzen']['stroom'], $result['prijs']['prijzen']['stroom']['levering'])){
            $newRes[ResourceInterface::ELECTRICITY_TARRIF_HIGH]     = ($result['prijs']['prijzen']['stroom']['levering']['per_eenheid'] != '') ? $result['prijs']['prijzen']['stroom']['levering']['per_eenheid'] : $result['prijs']['prijzen']['stroom']['levering']['hoog'];
            $newRes[ResourceInterface::ELECTRICITY_TARRIF_LOW]      = ($result['prijs']['prijzen']['stroom']['levering']['per_eenheid'] != '') ? 0 : $result['prijs']['prijzen']['stroom']['levering']['laag'];
            $newRes[ResourceInterface::ELECTRICITY_STANDING_CHARGE] = $result['prijs']['prijzen']['stroom']['levering']['vastrecht'];
            $newRes[ResourceInterface::ELECTRICITY_TOTAL_COSTS]     = $result['prijs_display']['stroom_verbruik_totaal']['value'];
            $newRes[ResourceInterface::ELECTRICITY_NETWORK_COSTS]   = $result['prijs_display']['stroom_netwerk_totaal']['value'];
            $newRes[ResourceInterface::ELECTRICITY_TAX]             = $result['prijs']['prijzen']['stroom']['belasting']['totaal'];
            $newRes[ResourceInterface::TAX_DISCOUNT]                = ResourceFilterHelper::array_get($result['prijs']['prijzen']['stroom']['belasting'], 'kortingtotaal');
        }else{
            $newRes[ResourceInterface::ELECTRICITY_TARRIF_HIGH]     = 0;
            $newRes[ResourceInterface::ELECTRICITY_TARRIF_LOW]      = 0;
            $newRes[ResourceInterface::ELECTRICITY_STANDING_CHARGE] = 0;
            $newRes[ResourceInterface::ELECTRICITY_TOTAL_COSTS]     = 0;
            $newRes[ResourceInterface::ELECTRICITY_NETWORK_COSTS]   = 0;
            $newRes[ResourceInterface::ELECTRICITY_TAX]             = 0;
        }

        if(isset($result['prijs']['prijzen']['gas'], $result['prijs']['prijzen']['gas']['levering'])){
            $newRes[ResourceInterface::GAS_TARRIF]          = $result['prijs']['prijzen']['gas']['levering']['per_eenheid'];
            $newRes[ResourceInterface::GAS_STANDING_CHARGE] = $result['prijs']['prijzen']['gas']['levering']['vastrecht'];
            $newRes[ResourceInterface::GAS_TAX]             = $result['prijs']['prijzen']['gas']['belasting']['totaal'];
            $newRes[ResourceInterface::GAS_TOTAL_COSTS]     = $result['prijs_display']['gas_verbruik_totaal']['value'];
            $newRes[ResourceInterface::GAS_NETWORK_COSTS]   = $result['prijs_display']['gas_netwerk_totaal']['value'];
            if( ! isset($newRes[ResourceInterface::TAX_DISCOUNT]) && isset($result['prijs']['prijzen']['gas']['belasting']['kortingtotaal'])){
                $newRes[ResourceInterface::TAX_DISCOUNT] = $result['prijs']['prijzen']['gas']['belasting']['kortingtotaal'];
            }
        }else{
            $newRes[ResourceInterface::GAS_TARRIF]          = 0;
            $newRes[ResourceInterface::GAS_STANDING_CHARGE] = 0;
            $newRes[ResourceInterface::GAS_TAX]             = 0;
            $newRes[ResourceInterface::GAS_TOTAL_COSTS]     = 0;
            $newRes[ResourceInterface::GAS_NETWORK_COSTS]   = 0;
        }
        $newRes[ResourceInterface::DISCOUNT] = isset($result['prijs_display']['totaal_korting_jaar']) ? $result['prijs_display']['totaal_korting_jaar']['value'] : 0;

        $newRes[ResourceInterface::DISCOUNT_DESCRIPTION]       = isset($result['tekst'], $result['tekst']['korting']) ? str_replace("{korting}", $newRes[ResourceInterface::DISCOUNT], $result['tekst']['korting']) : "";
        $newRes[ResourceInterface::DISCOUNT_DESCRIPTION_SHORT] = isset($result['tekst'], $result['tekst']['kortingKort']) ? str_replace("{korting}", $newRes[ResourceInterface::DISCOUNT], $result['tekst']['kortingKort']) : "";

        $newRes[ResourceInterface::TOTAL_COSTS_NO_DISCOUNT] = isset($result['prijs_display']['totaal_jaar_geen_korting']) ? $result['prijs_display']['totaal_jaar_geen_korting']['value'] : $result['prijs_display']['totaal_jaar']['value'];
        $newRes[ResourceInterface::TOTAL_COSTS]             = $result['prijs_display']['totaal_jaar']['value'];


        //add all results as underscore
        $flattenRes = $this->flattenUnderscore($result);

        //display crap
        $flattenRes['prijs_prijzen_transport_totaal']                 = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_transport_totaal') + ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_transport_totaal');
        $flattenRes['producten_stroom_type']                          = (isset($flattenRes['producten_stroom_groen']) && $flattenRes['producten_stroom_groen'] == 'true') ? 'Groen' : 'Grijs';
        $flattenRes['producten_gas_type']                             = (isset($flattenRes['producten_gas_groen']) && $flattenRes['producten_gas_groen'] == 'true') ? 'Groen' : 'Grijs';
        $flattenRes['producten_stroom_energielabel_percentage_grijs'] = 100 - ResourceFilterHelper::array_get($flattenRes, 'producten_stroom_energielabel_percentage_groen');
        $flattenRes['aanbieder_energy_extrainfo_lid_gedragscode'] .= ' ';
        $flattenRes['aanbieder_energy_extrainfo_lid_geschillencommissie'] .= ' ';
        $flattenRes['aanbieder_energy_extrainfo_acceptgiro_kosten'] .= ' ';
        $flattenRes['prijs_prijzen_stroom_belasting_totaal_korting'] = - ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_totaal_korting');

        $newRes['stroom_tarieven'] = isset($flattenRes['producten_stroom_contract_vrijopzegbaar']) ? (ResourceFilterHelper::strToBool($flattenRes['producten_stroom_contract_vrijopzegbaar']) ? 'variabel' : 'vast') : 'nvt';
        $newRes['gas_tarieven']    = isset($flattenRes['producten_gas_contract_vrijopzegbaar']) ? (ResourceFilterHelper::strToBool($flattenRes['producten_gas_contract_vrijopzegbaar']) ? 'variabel' : 'vast') : 'nvt';

        $newRes['stroom_ode_0_totaal']              = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_0_tarief_ode') * ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_0_verbruik');
        $newRes['stroom_energiebelasting_0_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_0_tarief') * ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_0_verbruik');

        $newRes['gas_ode_totaal']              = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_belasting_0_tarief_ode') * ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_belasting_0_verbruik');
        $newRes['gas_energiebelasting_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_belasting_0_tarief') * ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_belasting_0_verbruik');

        $newRes['prijs_prijzen_gas_levering_single_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_belasting_0_tarief_ode') + ResourceFilterHelper::array_get($flattenRes,
                'prijs_prijzen_gas_belasting_0_tarief') + ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_levering_per_eenheid');

        $newRes['prijs_prijzen_gas_levering_single_totaal_prijs'] = $newRes['prijs_prijzen_gas_levering_single_totaal'] * ResourceFilterHelper::array_get($this->params, 'verbruik_gas');

        $newRes['prijs_prijzen_gas_levering_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_gas_levering_prijs') + ResourceFilterHelper::array_get($flattenRes,
                'prijs_prijzen_gas_levering_vastrecht') + ResourceFilterHelper::array_get($newRes, 'gas_ode_totaal') + ResourceFilterHelper::array_get($newRes, 'gas_energiebelasting_totaal');

        $newRes[ResourceInterface::ELECTRICITY_USAGE_TOTAL] = ResourceFilterHelper::array_get($this->params, 'verbruik_stroom_1') + ResourceFilterHelper::array_get($this->params, 'verbruik_stroom_2');
        //extra belasting tarieven
        if($newRes[ResourceInterface::ELECTRICITY_USAGE_TOTAL] > 10000){
            $newRes['stroom_ode_1_totaal']              = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_1_tarief_ode') * ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_1_verbruik');
            $newRes['stroom_energiebelasting_1_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_1_tarief') * ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_1_verbruik');
        }
        if(ResourceFilterHelper::array_get($this->params, 'verbruik_stroom_2') > 0){
            $newRes['prijs_prijzen_stroom_levering_hoog_prijs']  = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_hoog') * ResourceFilterHelper::array_get($this->params, 'verbruik_stroom_1');
            $newRes['prijs_prijzen_stroom_levering_laag_prijs']  = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_laag') * ResourceFilterHelper::array_get($this->params, 'verbruik_stroom_2');
            $newRes['prijs_prijzen_stroom_levering_laag_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_0_tarief_ode') + ResourceFilterHelper::array_get($flattenRes,
                    'prijs_prijzen_stroom_belasting_0_tarief') + ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_laag');
            $newRes['prijs_prijzen_stroom_levering_hoog_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_0_tarief_ode') + ResourceFilterHelper::array_get($flattenRes,
                    'prijs_prijzen_stroom_belasting_0_tarief') + ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_hoog');
            $taxAmount                                           = ResourceFilterHelper::array_get($newRes, 'stroom_ode_0_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_ode_1_totaal') + ResourceFilterHelper::array_get($newRes,
                    'stroom_energiebelasting_0_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_energiebelasting_1_totaal');

            $weight                                                    = ResourceFilterHelper::array_get($this->params, 'verbruik_stroom_1') / ($newRes[ResourceInterface::ELECTRICITY_USAGE_TOTAL]);
            $newRes['prijs_prijzen_stroom_levering_hoog_totaal_prijs'] = ResourceFilterHelper::array_get($newRes, 'prijs_prijzen_stroom_levering_hoog_prijs') + (($taxAmount) * ($weight));

            $weight                                                    = ResourceFilterHelper::array_get($this->params, 'verbruik_stroom_2') / ($newRes[ResourceInterface::ELECTRICITY_USAGE_TOTAL]);
            $newRes['prijs_prijzen_stroom_levering_laag_totaal_prijs'] = ResourceFilterHelper::array_get($newRes, 'prijs_prijzen_stroom_levering_laag_prijs') + (($taxAmount) * ($weight));


            $newRes['prijs_prijzen_stroom_levering_totaal'] = ResourceFilterHelper::array_get($newRes, 'prijs_prijzen_stroom_levering_hoog_prijs') + ResourceFilterHelper::array_get($newRes,
                    'prijs_prijzen_stroom_levering_laag_prijs') + ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_vastrecht') + ResourceFilterHelper::array_get($newRes,
                    'stroom_ode_0_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_ode_1_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_energiebelasting_0_totaal') + ResourceFilterHelper::array_get($newRes,
                    'stroom_energiebelasting_1_totaal') - ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_totaal_korting');

            $newRes['prijs_prijzen_levering_totaal'] = ResourceFilterHelper::array_get($newRes, 'prijs_prijzen_stroom_levering_totaal') + ResourceFilterHelper::array_get($newRes, 'prijs_prijzen_gas_levering_totaal');
        }else{
            $newRes['prijs_prijzen_stroom_levering_single_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_0_tarief_ode') + ResourceFilterHelper::array_get($flattenRes,
                    'prijs_prijzen_stroom_belasting_0_tarief') + ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_per_eenheid');

            $newRes['prijs_prijzen_stroom_levering_single_totaal_prijs'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_prijs') + + ResourceFilterHelper::array_get($newRes,
                    'stroom_ode_0_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_ode_1_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_energiebelasting_0_totaal') + ResourceFilterHelper::array_get($newRes,
                    'stroom_energiebelasting_1_totaal');


            $newRes['prijs_prijzen_stroom_levering_totaal'] = ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_levering_prijs') + + ResourceFilterHelper::array_get($flattenRes,
                    'prijs_prijzen_stroom_levering_vastrecht') + ResourceFilterHelper::array_get($newRes, 'stroom_ode_0_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_ode_1_totaal') + ResourceFilterHelper::array_get($newRes,
                    'stroom_energiebelasting_0_totaal') + ResourceFilterHelper::array_get($newRes, 'stroom_energiebelasting_1_totaal') - ResourceFilterHelper::array_get($flattenRes, 'prijs_prijzen_stroom_belasting_totaal_korting');

            $newRes['prijs_prijzen_levering_totaal'] = ResourceFilterHelper::array_get($newRes, 'prijs_prijzen_stroom_levering_totaal') + ResourceFilterHelper::array_get($newRes, 'prijs_prijzen_gas_levering_totaal');
        }


        if( ! isset($flattenRes['prijs_display_totaal_jaar_geen_korting_value']) || $flattenRes['prijs_display_totaal_jaar_geen_korting_value'] == 0){
            $flattenRes['prijs_display_totaal_jaar_geen_korting_value'] = $flattenRes['prijs_display_totaal_jaar_value'];
            $flattenRes['prijs_display_totaal_korting_jaar_value']      = 0;
        }

        $newRes['price_admin']      = isset($flattenRes['prijs_administratiekosten'])?$flattenRes['prijs_administratiekosten']:0;
        $newRes['price_admin_flag'] = ( $newRes['price_admin']  > 0) ? 'show' : 'hide';
        $newRes['discount_flag'] = ($flattenRes['prijs_display_totaal_korting_jaar_value'] > 0) ? 'show' : 'hide';


        //extra shit
        //        if (isset($this->params[ResourceInterface::BUSINESS]) && ResourceFilterHelper::strToBool($this->params[ResourceInterface::BUSINESS])) {
        //            if (isset($this->params[ResourceInterface::CURRENT_PROVIDER]) && $this->params[ResourceInterface::CURRENT_PROVIDER] != -1) {
        //                $flattenRes['extralabel0'] = $flattenRes['aanbieder_generic_overstap_proces_business_switch_0_tekst_kort'];
        //            } else {
        //                //die('2');
        //            }
        //
        //        } else {
        //            if (isset($this->params[ResourceInterface::CURRENT_PROVIDER]) && $this->params[ResourceInterface::CURRENT_PROVIDER] != -1) {
        //              //  die('3');
        //            } else {
        //               // die('4');
        //            }
        //        }

        $newRes = array_merge($flattenRes, $newRes);

        //        dd($newRes);

        //$newRes[ResourceInterface::FREELY_TERMINABLE] = $result['producten'][$result['type']];

        //
        //Prijsopbouw
        //- Stroomtarief ( bij enkele meter )
        //- Stroomtarief Normaal ( bij dubbele meter )
        //- Stroomtarief Dal ( bij dubbele meter )
        //- Gastarief
        //- Vastrecht gas
        //- Vastrecht stroom:
        //- Energiebelasting stroom
        //- Energiebelasting gas
        //- Opslag duurzame energie stroom
        //- Opslag duurzame energie gas
        //- Heffingskorting
        //- Netbeheerkosten stroom
        //- Netbeheerkosten gas
        //
        //Eventuele Kortingen
        //- Voorzien van actievoorwaarden
        //- Voorzien van uitleg van de actie
        return $newRes;

    }

    /**
     * Copy of compare, fucking nasty
     *
     * @param array $params
     *
     * @return array
     */

    public function filterParamKeys(Array $params)
    {
        $this->basicAuthService['method_url'] .= $params[ResourceInterface::CONTRACT_ID];
        return parent::compareFilterParamKeys($params);
    }

}