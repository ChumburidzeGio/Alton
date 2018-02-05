<?php
namespace App\Resources\MeeusCCS\Methods;

use App\Exception\PrettyServiceError;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Models\Resource;
use App\Resources\MeeusCCS\MeeusCCSAbstractRequest;
use Illuminate\Support\Facades\Config;

/**
 * Get carinsurance premiums from the CCS data webservice in use by Meeus.
 */
class CalculatePremium extends MeeusCCSAbstractRequest
{
    protected $cacheDays = false;

    protected $methodName = 'VoorlopigePremieberekening';

    public $inputTransformations = [
        ResourceInterface::CAR_DEPRECIATION_SCHEME => 'mapCarDepricationScheme',
        ResourceInterface::SECURITY_CLASS_ID => 'mapSecurityClass',
        ResourceInterface::TURBO => 'castBoolToJN',
    ];
    public $inputToExternalMapping = [
        ResourceInterface::PRODUCT_ID => 'polisversie.commercieelproductnummer',
        ResourceInterface::COMPANY_ID => 'polisversie.maatschappijnummer',
        ResourceInterface::PAYMENT_PERIOD => 'polisversie.termijn',
        ResourceInterface::LICENSEPLATE => 'polisversie.subpolis.mr.kenteken',
        ResourceInterface::BIRTHDATE => 'polisversie.subpolis.mr.geboortedatum',
        ResourceInterface::YEARS_WITHOUT_DAMAGE => 'polisversie.subpolis.mr.bm.schadevrijejaren',
        ResourceInterface::MILEAGE => 'polisversie.subpolis.mr.bm.kilometrage',
        ResourceInterface::START_DATE => 'polisversie.ingangsdatum',
        ResourceInterface::POSTAL_CODE => 'polisversie.subpolis.mr.postcodevolledig',
        ResourceInterface::HOUSE_NUMBER => 'polisversie.subpolis.mr.huisnummer', // Not sure?
        ResourceInterface::CAR_DEPRECIATION_SCHEME => 'polisversie.subpolis.mr.afschrijvingsregeling',

        // Cardata (only used if overwritten)
        ResourceInterface::AMOUNT_OF_DOORS => 'polisversie.subpolis.mr.aantaldeuren',
        ResourceInterface::CONSTRUCTION_DATE => 'polisversie.subpolis.mr.afgiftedatumdeel1',
        ResourceInterface::SECURITY => 'polisversie.subpolis.mr.alarm',
        ResourceInterface::SECURITY_CLASS_ID => 'polisversie.subpolis.mr.alarmklassetno',
        ResourceInterface::TRANSMISSION_TYPE => 'polisversie.subpolis.mr.automaat',
        ResourceInterface::BPM_VALUE => 'polisversie.subpolis.mr.bedragbpm',
        ResourceInterface::PRICE_VAT => 'polisversie.subpolis.mr.bedragbtw',
        ResourceInterface::CONSTRUCTION_DATE_YEAR => 'polisversie.subpolis.mr.bouwjaar',
        ResourceInterface::CONSTRUCTION_DATE_MONTH => 'polisversie.subpolis.mr.bouwmaand',
        ResourceInterface::FUEL_TYPE_ID => 'polisversie.subpolis.mr.brandstofcode',
        ResourceInterface::NET_VALUE => 'polisversie.subpolis.mr.catalogusprijsexclusiefbtw',
        ResourceInterface::REPLACEMENT_VALUE => ['polisversie.subpolis.mr.catalogusprijsinclusiefbtw', 'polisversie.subpolis.mr.verzekerdbedragcasco'],
        ResourceInterface::CYLINDER_VOLUME => 'polisversie.subpolis.mr.cylinderinhoud',
        ResourceInterface::DAILY_VALUE => ['polisversie.subpolis.mr.dagwaarde', 'polisversie.subpolis.mr.dagwaardeinclusiefbtw'],
        ResourceInterface::WEIGHT => 'polisversie.subpolis.mr.gewicht',
        ResourceInterface::COLOR => 'polisversie.subpolis.mr.kleurcode',
        ResourceInterface::LOAD_CAPACITY => 'polisversie.subpolis.mr.laadvermogen',
        ResourceInterface::BRAND_NAME => 'polisversie.subpolis.mr.merk',
        ResourceInterface::MODEL_NAME => 'polisversie.subpolis.mr.model',
        ResourceInterface::POWER => 'polisversie.subpolis.mr.motorvermogen',
        ResourceInterface::BODY_TYPE => 'polisversie.subpolis.mr.objectcode',
        ResourceInterface::DRIVE_TYPE => 'polisversie.subpolis.mr.soortaandrijving',
        ResourceInterface::TURBO => 'polisversie.subpolis.mr.turbocode',
        ResourceInterface::TYPE_NAME => 'polisversie.subpolis.mr.type',
    ];

    protected $coverageToFrontOfficeCode = [
        'wa' => 'MRP-WA',
        'bc' => 'MRP-BC',
        'vc' => 'MRP-CA',
    ];

    protected $carDeprecationToCCSCode = [
        ResourceValue::DEPRECIATION_STANDARD => 'S',
        ResourceValue::DEPRECIATION_CURRENT_NEW_VALUE => 'A',
        ResourceValue::DEPRECIATION_PURCHASED_VALUE => 'N',
    ];

    protected $securityClassIdToTNOValue = [
        0 => '00',
        1 => '01',
        2 => '02',
        3 => '03',
        4 => '04',
        5 => '05',
    ];


    protected $productComboData = [];

    public function __construct()
    {
        parent::__construct('data/meeus_ccs_wsdls/'. ((app()->configure('resource_meeus')) ? '' : config('resource_meeus.settings.wsdl_environment')) .'_VoorlopigepremieService.wsdl');
    }

    protected function getDefaultParams()
    {
        return [
            'relatienummer' => 0,
            'polisversie' => [
                'contractduur' => 12, // Always 12
                'termijn' => 1, // Payment period, 1, 3, 6, 12 (always possible)
                'subpolis' => [
                    'mutatieredenfo' => 1000, // Nieuwe polis
                    'soortverzekering' => 'MR',
                    'mr' => [
                        'soortdekkingmr' => 1, // Type of coverage to request (not relevant for calculating, but maybe for creation)
                        'schadevrijejarer' => [
                            'pc' => 'N',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function setParams(array $params)
    {
        $carData = ResourceHelper::callResource2('licenseplate.carinsurance.meeusccs', [
            ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
            ResourceInterface::PRODUCT_ID => $params[ResourceInterface::PRODUCT_ID], // CCS can return different car data / codes for some insurance providers
        ]);

        if (isset($carData['xml'])) {
            $carDataRaw = $carData['xml'];
        }
        else {
            $params = array_merge($carData, $params);
        }

        $coverage = array_get($params, ResourceInterface::COVERAGE);

        parent::setParams($params);

        $this->productComboData = $this->getProductComboData(array_get($params, ResourceInterface::PRODUCT_ID), $coverage);

        if (isset($carDataRaw)) {
            foreach ($carDataRaw as $key => $value) {
                array_set($this->params, 'polisversie.subpolis.mr.'. $key, $value);
            }
        }

        array_set($this->params, 'polisversie.productcode', $this->productComboData['productCode']);
        array_set($this->params, 'polisversie.maatschappijnummer', $this->productComboData['maatschappijnummer']);
        array_set($this->params, 'polisversie.subpolis.maatschappijnummer', $this->productComboData['maatschappijnummer']);
        array_set($this->params, 'polisversie.subpolis.mr.soortdekkingmr', $this->productComboData['coverageDekking']['volgnummer']);
        array_set($this->params, 'polisversie.subpolis.mr.kilometrage', $this->productComboData['kilometrage']);
        array_set($this->params, 'polisversie.subpolis.mr.eigenrisico', $this->productComboData['eigenRisico']);
        array_set($this->params, 'polisversie.subpolis.mr.dekking', $this->productComboData['coverageDekkingen']);
        array_set($this->params, 'polisversie.subpolis.mr.objectvrij', ['pc' => 'N'] + $this->productComboData['customFields']);
    }

    public function getProductComboData($productId, $coverage)
    {
        $coverageFrontOfficeCode = $this->coverageToFrontOfficeCode[$coverage];

        // We can overload the coverage-code in the product id
        if (str_contains($productId, '-'))
            list($productId, $coverageFrontOfficeCode) = explode('-', $productId, 2);

        $productInfo = ResourceHelper::callResource2('get_product_info.carinsurance.meeusccs', [ResourceInterface::PRODUCT_ID => $productId]);

        // Find the 'dekking' for the desired main coverage
        $dekkingsCodesFull = [];
        $coverageDekking = null;
        foreach ($productInfo['polisversie']['subpolis']['dekking'] as $dekking) {
            if ($dekking['frontofficecode'] == $coverageFrontOfficeCode) {
                $dekkingsCodesFull[] = ($dekking['dekkingskenmerk'] ? $dekking['dekkingskenmerk'] : 'MR'). $dekking['dekkingscode'];
                $coverageDekking = $dekking;
                break;
            }
        }

        if (!$coverageDekking)
            throw new \Exception('Could not find matching main coverage dekking for coverage `'. $coverage .'`, frontofficecode `'. $coverageFrontOfficeCode .'`');

        // See which smallest combo contains this 'dekkingcode', take the productCode from there;
        $comboSize = 9999;
        $productCode = null;
        $selectedCombo = null;
        foreach ($productInfo['polisversie']['subpolis']['mijcommproductcomb'] as $combo) {
            $combo['dekkingen'] = explode(',', $combo['samenstelling']);
            if (array_diff($dekkingsCodesFull, $combo['dekkingen']) === [] && count($combo['dekkingen']) <= $comboSize) {
                $productCode = $combo['productcode'];
                $comboSize = count($combo['dekkingen']);
                $selectedCombo = $combo;
            }
        }

        if (!$selectedCombo)
            dd($productInfo, $dekkingsCodesFull);

        // Find the appropriate own risk requested
        $ownRisks = [];
        $ownRisk = 0;
        if (isset($productInfo['mijcommproductfilter']['Polisversie.subpolis.mr.dekking.EigenRisico']))
        {
            $ownRisks = [];
            foreach ($productInfo['mijcommproductfilter']['Polisversie.subpolis.mr.dekking.EigenRisico'] as $ownRiskOptions) {
                foreach ($ownRiskOptions['tabelfilter'] as $ownRiskOption) {
                    if (isset($this->inputParams[ResourceInterface::CALCULATION_OWN_RISK])) {
                        if ($ownRiskOption['waarde'] <= $this->inputParams[ResourceInterface::CALCULATION_OWN_RISK]) {
                            $ownRisks[$ownRiskOptions['frontofficecode']] = $ownRiskOption['waarde'];
                        }
                    } else if ($ownRiskOption['default']) {
                        $ownRisks[$ownRiskOptions['frontofficecode']] = $ownRiskOption['waarde'];
                    }
                }
                // If there are own risks, but there is no default, pick the first one
                if (count($ownRiskOptions['tabelfilter']) > 0 && !isset($ownRisks[$ownRiskOptions['frontofficecode']])) {
                    $ownRisks[$ownRiskOptions['frontofficecode']] = head($ownRiskOptions['tabelfilter'])['waarde'];
                }

                if ($ownRiskOptions['frontofficecode'] == $coverageDekking['frontofficecode']) {
                    $ownRisk = array_get($ownRisks, $ownRiskOptions['frontofficecode'], null);
                }
            }
        }
        // Own risks values might also be defined in the 'vrije rubrieken', aka custom fields.
        // (this happens in the 'Delta Lloyd' product)
        $customFields = [];
        if (isset($productInfo['tabelvrijerubriekgroep']['mijcommprodvrij'])) {
            foreach ($productInfo['tabelvrijerubriekgroep']['mijcommprodvrij'] as $customField) {
                if ($customField['productcode'] == $selectedCombo['productcode'] && $customField['rubrieknaam'] === 'Eigen risico') {
                    foreach ($customField['mijcommprodvrijtabel'] as $ownRiskOption) {
                        if (isset($this->inputParams[ResourceInterface::CALCULATION_OWN_RISK])) {
                            if ((int)$ownRiskOption['code'] <= (int)$this->inputParams[ResourceInterface::CALCULATION_OWN_RISK]) {
                                $customFields['rubriek'. $customField['volgnummerrubriek']] = (string)$ownRiskOption['code'];
                            }
                        }
                    }
                    if (isset($customField['defaultwaarde']) && !isset($customFields['rubriek'. $customField['volgnummerrubriek']])) {
                        $customFields['rubriek'. $customField['volgnummerrubriek']] = (string)$customField['defaultwaarde'];
                    }

                    $ownRisk = $customFields['rubriek'. $customField['volgnummerrubriek']];
                }
            }
        }

        // Create `dekkingen` data for all other products in this combo
        $coverageDekkingen = [];
        $selectedCombo['frontOfficeCodes'] = [];
        foreach ($selectedCombo['dekkingen'] as $comboDekkingsCode) {
            foreach ($productInfo['polisversie']['subpolis']['dekking'] as $dekking) {
                $dekkingsCodeFull = ($dekking['dekkingskenmerk'] ? $dekking['dekkingskenmerk'] : 'MR'). $dekking['dekkingscode'];
                if ($comboDekkingsCode == $dekkingsCodeFull) {
                    $selectedCombo['frontOfficeCodes'][] = $dekking['frontofficecode'];
                    $coverageDekkingen[] = [
                        'dekkingscode' => $dekking['dekkingscode'],
                        'frontofficecode' => $dekking['frontofficecode'],
                        'geselecteerdfo' => 'T',
                        'pc' => 'N',
                    ] + (isset($ownRisks[$dekking['frontofficecode']]) ? ['eigenrisico' => $ownRisks[$dekking['frontofficecode']]] : []);
                }
            }
        }


        // Find the appropriate mileage
        $mileage = null;
        if (isset($productInfo['mijcommproductfilter']['Polisversie.subpolis.mr.Kilometrage'][0]))
        {
            foreach ($productInfo['mijcommproductfilter']['Polisversie.subpolis.mr.Kilometrage'][0]['tabelfilter'] as $mileageOption) {
                if (isset($this->inputParams[ResourceInterface::MILEAGE])) {
                    if ($mileageOption['waarde'] >= $this->inputParams[ResourceInterface::MILEAGE]) {
                        $mileage = $mileageOption['waarde'];
                        break;
                    }
                }
                else if ($mileageOption['default']) {
                    $mileage = $mileageOption['waarde'];
                }
            }
        }
        if (!$mileage && isset($productInfo['mijcommprodparameter']['MR_Kilometrage_Onbeperkt'])) {
            $mileage = $productInfo['mijcommprodparameter']['MR_Kilometrage_Onbeperkt']['validatiewaarde'];
        }

        if (!$productCode)
            throw new \Exception('Could not find matching product code for coverage `'. $coverage .'`');

        return [
            'productCode' => $productCode,
            'coverageFrontOfficeCode' => $coverageFrontOfficeCode,
            'coverageDekking' => $coverageDekking,
            'coverageDekkingen' => $coverageDekkingen,
            'dekkingsCodes' => [$coverageDekking['dekkingscode']],
            'coverageDekkingCodeFull' => ($coverageDekking['dekkingskenmerk'] ? $coverageDekking['dekkingskenmerk'] : 'MR'). $coverageDekking['dekkingscode'],
            'combo' => $selectedCombo,
            'maatschappijnummer' => $productInfo['polisversie']['maatschappijnummer'],
            'eigenRisico' => $ownRisk,
            'kilometrage' => $mileage,
            'customFields' => $customFields,
        ];
    }

    public function castBoolToJN($value)
    {
        return $value ? 'J' : 'N';
    }

    protected function mapCarDepricationScheme($value)
    {
        return array_get($this->carDeprecationToCCSCode, $value);
    }

    protected function mapSecurityClass($value)
    {
        return array_get($this->securityClassIdToTNOValue, $value);
    }

    public function getResult()
    {
        $data = parent::getResult();

        if ($this->productComboData['eigenRisico'] === null) {
            // An own risk was requested, and we could not find any
            return [];
        }

        $result = [
            ResourceInterface::PRICE_DEFAULT => 0,
            ResourceInterface::OWN_RISK => $this->productComboData['eigenRisico'],
            ResourceInterface::USED_MILEAGE => $this->productComboData['kilometrage'],
            ResourceInterface::PRODUCT_ID => $this->inputParams[ResourceInterface::PRODUCT_ID],
        ];
        foreach (array_get($data, 'subpolis.dekking') as $coverage) {
            if (in_array($coverage['frontofficecode'], $this->productComboData['combo']['frontOfficeCodes'])) {
                $result[ResourceInterface::PRICE_DEFAULT] += $coverage['nettopremie'] + $coverage['assurantiebelasting'];

                if ($coverage['nettopremie'] + $coverage['assurantiebelasting'] == 0 && $coverage['dekkingscode'] == 100) {
                    throw new PrettyServiceError(Resource::where('name', 'premium.carinsurance.meeusccs')->firstOrFail(), $this->inputParams, 'Main coverage has 0 price: '. json_encode($coverage), []);
                }
                if (!empty($coverage['toelichting'])) {
                    throw new PrettyServiceError(Resource::where('name', 'premium.carinsurance.meeusccs')->firstOrFail(), $this->inputParams, 'Coverage has toelichting: '. json_encode($coverage), []);
                }
            }
        }

        $paymentPeriod = array_get($this->inputParams, ResourceInterface::PAYMENT_PERIOD, 1);
        if ($paymentPeriod != 1) {
            $result[ResourceInterface::PRICE_DEFAULT] = $result[ResourceInterface::PRICE_DEFAULT] / $paymentPeriod;
        }

        return [$result];
    }
}