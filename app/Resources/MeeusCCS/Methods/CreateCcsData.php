<?php
namespace App\Resources\MeeusCCS\Methods;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Models\Resource;
use App\Resources\AbstractMethodRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;


class CreateCcsData extends AbstractMethodRequest
{
    protected $cacheDays = false;
    public $resource2Request = true;

    protected $params = [];
    protected $result;

    protected $productIdToCcsProduct = [
        // TODO:  ASR product ID not know currently
        '7008' => [
            'ccs_cpd_id' => '2607',
            'ccs_name' => 'Personenautoverzekering ASR',
            'ccs_maatschappijnummer' => 'V059P',
        ],
        // "Allianz Veilig op Weg Pakket met adviseur"
        '7003' => [
            'ccs_cpd_id' => '2703',
            'ccs_name' => 'Personenautoverzekering Allianz',
            'ccs_maatschappijnummer' => 'V021P',
        ],
        // "Reaal Autoverzekering (adviseur)"
        '7005' => [
            'ccs_cpd_id' => '2803',
            'ccs_name' => 'Personenautoverzekering Reaal',
            'ccs_maatschappijnummer' => 'V043P',
        ],
        // "UMG Autoverzekering"
        '7007' => [
            'ccs_cpd_id' => '4000',
            'ccs_name' => 'Meeùs Personenauto',
            'ccs_maatschappijnummer' => 'M001',
        ],
        // "Aegon Autoverzekering"
        '7009' => [
            'ccs_cpd_id' => '2901',
            'ccs_name' => 'Aegon Autoverzekering Allrisk Basis',
            'ccs_maatschappijnummer' => 'V703P',
            'ccs_soortdekking' => 3,
        ],
        // "Aegon Autoverzekering"
        '7010' => [
            'ccs_cpd_id' => '2901',
            'ccs_name' => 'Aegon Autoverzekering Allrisk Compleet',
            'ccs_maatschappijnummer' => 'V703P',
            'ccs_soortdekking' => 4,
        ],
        // "Aegon Autoverzekering"
        '7011' => [
            'ccs_cpd_id' => '2901',
            'ccs_name' => 'Aegon Autoverzekering Allrisk Royaal',
            'ccs_maatschappijnummer' => 'V703P',
            'ccs_soortdekking' => 5,
        ],
        // "Aegon Autoverzekering"
        '7012' => [
            'ccs_cpd_id' => '2901',
            'ccs_name' => 'Aegon Autoverzekering WA',
            'ccs_maatschappijnummer' => 'V703P',
            'ccs_soortdekking' => 1,
        ],
        // "Aegon Autoverzekering"
        '7013' => [
            'ccs_cpd_id' => '2901',
            'ccs_name' => 'Aegon Autoverzekering WA Extra',
            'ccs_maatschappijnummer' => 'V703P',
            'ccs_soortdekking' => 2,
        ],
        // "Generali Autoverzekering"
        '7100' => [
            'ccs_cpd_id' => '3303',
            'ccs_name' => 'Generali Autoverzekering',
            'ccs_maatschappijnummer' => 'V505P',
        ],
        // "Delta Lloyd Autoverzekering"
        '7102' => [
            'ccs_cpd_id' => '3203',
            'ccs_name' => 'Delta Lloyd Autoverzekering',
            'ccs_maatschappijnummer' => 'V016P',
            'custom_own_risk_bc' => 'rubriek4',
            'custom_own_risk_vc' => 'rubriek4',
        ],
        // Onbekend?
        'UNKNOWN_4008' => [
            'ccs_cpd_id' => '4008',
            'ccs_name' => 'Personenautoverzekering',
            'ccs_maatschappijnummer' => 'M001',
        ],
        // Onbekend?
        'UNKNOWN_4050' => [
            'ccs_cpd_id' => '4050',
            'ccs_name' => 'Reaal Personenauto',
            'ccs_maatschappijnummer' => 'V043P',
        ],
    ];

    protected $rollsFuelIdToBrandstofCode = [
        'Benzine' => 'B',
        'Diesel' => 'D',
        'Electrisch' => 'E',
        'Hybride-petrol' => 'J',
        'Hybride-diesel' => 'I',
        'LPG' => 'A',
        'CNG' => 'A', // Fallback
        'Waterstof' => 'E', // Fallback
        'Anders' => null,
    ];

    protected $colorToKleurCode = [

    ];

    protected $coverageToSoortDekkingMr = [
        'wa' => 1,
        'bc' => 2,
        'vc' => 3,
    ];

    protected $coverageToFrontOfficeCode = [
        'wa' => 'MRP-WA',
        'bc' => 'MRP-BC',
        'vc' => 'MRP-CA',
    ];


    protected $carDepreciationSchemeToAfschrijvingsRegeling = [
        ResourceValue::DEPRECIATION_CURRENT_NEW_VALUE => 'N',
        ResourceValue::DEPRECIATION_PURCHASED_VALUE => 'A',
        ResourceValue::DEPRECIATION_STANDARD => 'S',
    ];

    public function setParams(Array $params)
    {
        $this->params = $params;
    }

    public function executeFunction()
    {
        $hasLicenseplate = !empty($this->params[ResourceInterface::LICENSEPLATE]);
        $hasOtherRegularDriver = isset($this->params[ResourceInterface::REGULAR_DRIVER]) && $this->params[ResourceInterface::REGULAR_DRIVER] != ResourceValue::REGULAR_DRIVER_MYSELF;

        if (isset($this->params[ResourceInterface::PRODUCT_ID]))
        {
            if (isset($this->productIdToCcsProduct[(string)$this->params[ResourceInterface::PRODUCT_ID]]))
                $this->params += $this->productIdToCcsProduct[(string)$this->params[ResourceInterface::PRODUCT_ID]];
            else
                $this->setErrorString('Cannot map product ID `'. $this->params[ResourceInterface::PRODUCT_ID] .'`');
        }
        else
        {
            $this->setErrorString('Missing product ID.');
        }

        // Get product information
        $product = [];
        if (!isset($this->params[ResourceInterface::USED_MILEAGE]) && isset($this->params[ResourceInterface::USER], $this->params[ResourceInterface::WEBSITE])) {
            try {
                $productParams = ['enabled' => 1, 'active' => 1, '_offset' => 0, '_limit' => 999999, '__id' => $this->params[ResourceInterface::PRODUCT_ID]] + $this->params;
                $products = ResourceHelper::callResource2('product.carinsurance', $productParams);

                if (count($products) != 1) {
                    Log::error('Meeus CSS product request failed - too many or too few results (' . count($products) . '). Input: ' . json_encode($productParams) . "\nOutput: " . json_encode($products));
                    $this->setErrorString('Product could not be found.');
                    return;
                }

                $product = head($products);
            } catch (\Exception $e) {
                // Ignore errors, but do report them in the logs
                Log::error('Meeus CCS product request failed: '. $e->getMessage() .' - Input: '. json_encode($productParams));
                $this->setErrorString('Product could not be found.');
                return;
            }
        }

        $coverage = array_get($this->coverageToSoortDekkingMr, array_get($this->params, ResourceInterface::COVERAGE, false));
        if (isset($this->params['ccs_soortdekking']))
            $coverage = $this->params['ccs_soortdekking'];

        $data = [
            // Customer input
            'polisversie[1].termijn' => array_get($this->params, ResourceInterface::PAYMENT_PERIOD),
            'polisversie[1].subpolis[1].mr[1].alarmklassetno' => isset($this->params[ResourceInterface::SECURITY_CLASS]) ? '0'. (int)$this->params[ResourceInterface::SECURITY_CLASS] : null, //'3',
            'polisversie[1].subpolis[1].mr[1].bmverklaring' => null, //isset($this->params[ResourceInterface::NO_CLAIM]) ? ($this->params[ResourceInterface::NO_CLAIM] ? 'true' : 'false') : null,
            'polisversie[1].subpolis[1].mr[1].geboortedatum' => $hasOtherRegularDriver ? null : (isset($this->params[ResourceInterface::BIRTHDATE]) ? date('d-m-Y', strtotime($this->params[ResourceInterface::BIRTHDATE])) : null),
            'polisversie[1].subpolis[1].mr[1].inclusiefbtw' => 'true', // If the returned insurance prices should be incl or excl VAT
            'polisversie[1].subpolis[1].mr[1].huisnummer' => array_get($this->params, ResourceInterface::HOUSE_NUMBER),
            'polisversie[1].subpolis[1].mr[1].ingangsdatum' => null, //date('d-m-Y'),
            'polisversie[1].subpolis[1].mr[1].postcodevolledig' => array_get($this->params, ResourceInterface::POSTAL_CODE),
            'polisversie[1].subpolis[1].mr[1].kilometrage' => array_get($this->params, ResourceInterface::USED_MILEAGE),
            'polisversie[1].subpolis[1].mr[1].bm[1].schadevrijejaren' => array_get($this->params, ResourceInterface::YEARS_WITHOUT_DAMAGE),
            'polisversie[1].subpolis[1].mr[1].afschrijvingsregeling' => isset($this->params[ResourceInterface::CAR_DEPRECIATION_SCHEME]) ? array_get($this->carDepreciationSchemeToAfschrijvingsRegeling, array_get($this->params, ResourceInterface::CAR_DEPRECIATION_SCHEME)) : null,
            'polisversie[1].subpolis[1].mr[1].soortdekkingmr' => $coverage,
            // Driver details
            'polisversie[1].subpolis[1].mr[1].bestuurder[1].postcode' => $hasOtherRegularDriver ? array_get($this->params, ResourceInterface::POSTAL_CODE) : null,
            'polisversie[1].subpolis[1].mr[1].bestuurder[1].huisnummer' => $hasOtherRegularDriver ? array_get($this->params, ResourceInterface::HOUSE_NUMBER) : null,
            'polisversie[1].subpolis[1].mr[1].bestuurder[1].geboortedatum' => $hasOtherRegularDriver ? (isset($this->params[ResourceInterface::BIRTHDATE]) ? date('d-m-Y', strtotime($this->params[ResourceInterface::BIRTHDATE])) : null) : null,
            // Car data
            'polisversie[1].subpolis[1].mr[1].kenteken' => array_get($this->params, ResourceInterface::LICENSEPLATE),
            'polisversie[1].subpolis[1].mr[1].isagebruik' => $hasLicenseplate ? 'N' : 'J', // Do custom car input
            // Car details
            'polisversie[1].subpolis[1].mr[1].merk' => array_get($this->params, ResourceInterface::BRAND_NAME),
            'polisversie[1].subpolis[1].mr[1].model' => array_get($this->params, ResourceInterface::MODEL_NAME),
            'polisversie[1].subpolis[1].mr[1].type' => array_get($this->params, ResourceInterface::TYPE_NAME),
            'polisversie[1].subpolis[1].mr[1].bouwjaar' => isset($this->params[ResourceInterface::CONSTRUCTION_DATE]) ? date('Y', strtotime($this->params[ResourceInterface::CONSTRUCTION_DATE])) : null,
            'polisversie[1].subpolis[1].mr[1].bouwmaand' => isset($this->params[ResourceInterface::CONSTRUCTION_DATE]) ? date('j', strtotime($this->params[ResourceInterface::CONSTRUCTION_DATE])) : null,
            'polisversie[1].subpolis[1].mr[1].afgiftedatumdeel1' => null, // We do not prefill this
            'polisversie[1].subpolis[1].mr[1].afgiftedatumdeel2' => null, // We do not prefill this
            'polisversie[1].subpolis[1].mr[1].automaat' => null, // 'A'?  We do not prefill prefill
            'polisversie[1].subpolis[1].mr[1].bedragbpm' => null, // We do not prefill this
            'polisversie[1].subpolis[1].mr[1].bedragbtw' => null, // We do not prefill this
            'polisversie[1].subpolis[1].mr[1].brandstofcode' => array_get($this->rollsFuelIdToBrandstofCode, array_get($this->params, ResourceInterface::FUEL_TYPE_ID, false)),
            'polisversie[1].subpolis[1].mr[1].catalogusprijsexclusiefbtw' => null, // We do not prefill this
            'polisversie[1].subpolis[1].mr[1].catalogusprijsinclusiefbtw' => array_get($this->params, ResourceInterface::REPLACEMENT_VALUE),
            'polisversie[1].subpolis[1].mr[1].cataloguswaarderolls' => array_get($this->params, ResourceInterface::REPLACEMENT_VALUE),
            'polisversie[1].subpolis[1].mr[1].cylinderinhoud' => array_get($this->params, ResourceInterface::CYLINDER_VOLUME),
            'polisversie[1].subpolis[1].mr[1].dagwaarde' => null,
            'polisversie[1].subpolis[1].mr[1].dagwaardeinclusiefbtw' => array_get($this->params, ResourceInterface::DAILY_VALUE),
            'polisversie[1].subpolis[1].mr[1].dagwaarderolls' => array_get($this->params, ResourceInterface::DAILY_VALUE),
            'polisversie[1].subpolis[1].mr[1].gewicht' => array_get($this->params, ResourceInterface::WEIGHT),
            'polisversie[1].subpolis[1].mr[1].gewichtrolls' => array_get($this->params, ResourceInterface::WEIGHT),
            'polisversie[1].subpolis[1].mr[1].kleurcode' => isset($this->params[ResourceInterface::COLOR]) ? array_get($this->colorToKleurCode, array_get($this->params, ResourceInterface::COLOR, false)) : null,
            'polisversie[1].subpolis[1].mr[1].laadvermogen' => null,
            'polisversie[1].subpolis[1].mr[1].motorvermogen' => array_get($this->params, ResourceInterface::POWER),
            'polisversie[1].subpolis[1].mr[1].mutatiedatum' => null, // Not relevant for us
            'polisversie[1].subpolis[1].mr[1].omsbrandstofcode' => null, // 'Omschrijving' - we do not pass this
            'polisversie[1].subpolis[1].mr[1].soortaandrijving' => null, //'VW',
            'polisversie[1].subpolis[1].mr[1].turbocode' => isset($this->params[ResourceInterface::TURBO]) ? ($this->params[ResourceInterface::TURBO] ? 'J' : 'N') : null,
            'polisversie[1].subpolis[1].mr[1].verzekerdbedragcasco' => array_get($this->params, ResourceInterface::REPLACEMENT_VALUE),
            // Misc / unknown
            'polisversie[1].subpolis[1].mr[1].objectcode' => null, // Not relevant for us
            // Own risks
        ];

        $coverage = array_get($this->params, ResourceInterface::COVERAGE, false);
        if($coverage != 'wa'){
            $data['polisversie[1].subpolis[1].mr[1].dekking[1].frontofficecode'] = array_get($this->coverageToFrontOfficeCode, array_get($this->params, ResourceInterface::COVERAGE, false));
            $data['polisversie[1].subpolis[1].mr[1].dekking[1].eigenrisico'] = array_get($this->params, ResourceInterface::OWN_RISK, 0);

        }

        $productMeta = $this->productIdToCcsProduct[$this->params[ResourceInterface::PRODUCT_ID]];
        if(isset($productMeta['custom_own_risk_' . $this->params[ResourceInterface::COVERAGE]])){
            $customFieldLabel = $productMeta['custom_own_risk_' . $this->params[ResourceInterface::COVERAGE]];
            $data['polisversie[1].subpolis[1].mr[1].objectvrij[1].' . $customFieldLabel] = array_get($this->params, ResourceInterface::OWN_RISK, 0);
        }

        // Get rid of all 'null' values
        $data = array_filter($data, function($var){return !is_null($var);} );

        $xml = '<?xml version="1.0" encoding="utf-8" standalone="no"?><PrefilledData><Product1>';
        foreach ($data as $xmlField => $value)
        {
            // This should never occur...
            if (!is_scalar($value)) {
                \Illuminate\Support\Facades\Log::error('CCS field `'. $xmlField .'` has a non-scalar value: '. json_encode($value));
                continue;
            }

            $xml .= '<KeyValuePair key="'. htmlspecialchars($xmlField) .'" value="'. htmlspecialchars($value) .'"></KeyValuePair>';
        }
        $xml .= '</Product1></PrefilledData>';

        $code = array_get($this->params, 'ccs_cpd_id', null);

        $this->result = [
            ResourceInterface::CODE => $code,
            ResourceInterface::URL => ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.meeus_ccs_form_url')),
            ResourceInterface::DATA => $xml,
        ];
    }

    public function getResult()
    {
        return $this->result;
    }
}