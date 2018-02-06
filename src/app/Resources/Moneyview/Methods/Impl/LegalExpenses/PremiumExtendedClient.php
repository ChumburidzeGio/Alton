<?php
namespace App\Resources\Moneyview\Methods\Impl\LegalExpenses;

use App\Interfaces\ResourceInterface;
use App\Resources\Moneyview\Methods\MoneyviewAbstractSoapRequest;
use Config;

class PremiumExtendedClient extends MoneyviewAbstractSoapRequest
{
    protected $insuredAmount;

    protected $arguments = [
        ResourceInterface::START_DATE => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber',
            'default' => '',
            'description' => 'Berekening_Ingangsdatum - Op welke datum moet de verzekering ingaan',
        ],
        ResourceInterface::BIRTHDATE => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'filter'  => 'filterNumber',
            'default' => '19700101',
            'description' => 'Persoon_Geboortedatum - Geboortedatum verzekerde',
        ],
        ResourceInterface::PERSON_SINGLE => [
            'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'legalexpensesinsurance',
                'method'   => 'list',
                'params'   => ['list' => ResourceInterface::PERSON_SINGLE],
                'field'    => ResourceInterface::SPEC_NAME,
            ],
            'description' => 'Persoonlijke_Omstandigheden - Gezinssituatie (LOOKUP)',
        ],
        ResourceInterface::POSTAL_CODE                  => [
            'rules'   => self::VALIDATION_POSTAL_CODE,
            'example' => '2011DW',
            'filter'  => 'filterNumber',
            'default' => '1012',
            'description' => 'Persoon_Postcode - Getal 1000 – 9999',
        ],
        ResourceInterface::CALCULATION_INSURED_AMOUNT => [
            /*'rules'         => self::VALIDATION_REQUIRED_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'legalexpensesinsurance',
                'method'   => 'list',
                'params'   => ['list' => ResourceInterface::CALCULATION_INSURED_AMOUNT],
                'field'    => ResourceInterface::SPEC_NAME,
            ],*/
            'rules'   => 'string',
            'default' => 12500,
            'description' => 'Berekening_Verzekerd_Bedrag - Verzekerd bedrag (LOOKUP)',
        ],
        ResourceInterface::CALCULATION_OWN_RISK => [
            'rules'   => 'number',
            'example' => '500',
            'filter'  => 'filterNumber',
            'default' => 0,
            'description' => 'Berekening_ER - Getal 1000 – 9999',
        ],
        ResourceInterface::CALCULATION_FRANCHISE => [
            'rules'   => 'number',
            'filter'  => 'filterNumber',
            'default' => 0,
            'description' => 'Berekening_Franchise - Getal 1000 – 9999',
        ],
        ResourceInterface::SELECTED_COVERAGES => [
            'rules'   => 'array',
            'default' => '',
            'description' => 'Comma-separated list of desired insurance coverages.',
        ],
        /*
        ResourceInterface::INSURE_CONSUMER => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Consument - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_FAMILY_LAW => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Familierecht - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_INCOME => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Inkomen - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_WORK => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Arbeid - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_TAXES_AND_CAPITAL => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Fiscaal_en_Vermogen - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_DIVORCE_MEDIATION => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_ScheidingsMediation - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_MEDICAL => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Medisch - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_HOUSING => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Wonen - [Ja]/[Nee]',
        ],*/
        ResourceInterface::HOUSE_OWNER                => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Persoon_Eigenwoning - [Ja]/[Nee]',
        ],
        /*
        ResourceInterface::INSURE_NEIGHBOUR_DISPUTES => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Burenrecht - [Ja]/[Nee]',
        ],*/
        ResourceInterface::IS_HOUSE_FOR_RENT => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Persoon_Verhuur_Eigenwoning - [Ja]/[Nee]',
        ],
        ResourceInterface::HOUSE_RENTED_LIVINGUNITS => [
            'rules'   => 'number',
            'example' => '0',
            'filter'  => 'filterNumber',
            'default' => 0,
            'description' => 'Persoon_Verhuur_Wooneenheden - Aantal verhuurde eenheden',
        ],
        ResourceInterface::HOUSE_RENTED_WORKUNITS => [
            'rules'   => 'number',
            'example' => '0',
            'filter'  => 'filterNumber',
            'default' => 0,
            'description' => 'Persoon_Verhuur_Bedrijfseenheden - Aantal verhuurde eenheden',
        ],
        ResourceInterface::VACATIONHOME_LOCATION => [
            /*
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'legalexpensesinsurance',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::VACATIONHOME_LOCATION
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            */
            'rules'       => 'string',
            'default'     => 'geen dekking',
            'description' => 'VakantieWoning_Locatie - Vakantiewoning (LOOKUP)',
            // Values: 'geen dekking', 'in Nederland', 'in het buitenland'
        ],
        /*
        ResourceInterface::INSURE_TRAFFIC => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Verkeer - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_TRAFFIC_ROADVEHICLE_ACCIDENT => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Motovoertuigen_Ongeval - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_TRAFFIC_ROADVEHICLE_OTHER => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Motovoertuigen_Overig - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_TRAFFIC_WATERVEHICLE_ACCIDENT => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Vaartuigen_Ongeval - [Ja]/[Nee]',
        ],
        ResourceInterface::INSURE_TRAFFIC_WATERVEHICLE_OTHER => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Verzekering_Vaartuigen_Overig - [Ja]/[Nee]',
        ],
        */

        ResourceInterface::PRICE_WATERVEHICLE_CATALOGUS => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Vaartuig_Cataloguswaarde - Cataloguswaarde vaartuig',
        ],
        ResourceInterface::PAYMENT_PERIOD => [
            'rules'   => 'number',
            'default' => '1',
            'description' => 'Berekening_Betalingstermijn - [Jaar] of [Maand]',
        ],
        ResourceInterface::RETURN_ALL_PRODUCTS => [
            'rules'   => self::VALIDATION_BOOLEAN,
            'filter'  => 'convertToDutchBool',
            'default' => false,
            'description' => 'Berekening_Nulpremies - [Ja] [Nee] (toon niet geaccepteerde producten)',
        ],
    ];

    protected $cacheDays = 1;

    protected $outputFields = [
        ResourceInterface::TITLE,
        ResourceInterface::SPEC_NAME,
        ResourceInterface::PRICE_ACTUAL,
        ResourceInterface::PRICE_COVERAGE_SUB_TOTAL,
        ResourceInterface::PRICE_INSURANCE_TAX,
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_INITIAL,
        ResourceInterface::PRICE_SURCHARGES,
        ResourceInterface::OWN_RISK,
        ResourceInterface::FRANCHISE,
        ResourceInterface::COVERAGE_AMOUNT,

        ResourceInterface::COVERAGE_CONSUMER,
        ResourceInterface::COVERAGE_INCOME,
        ResourceInterface::COVERAGE_TRAFFIC,
        ResourceInterface::COVERAGE_HOUSING,
        ResourceInterface::COVERAGE_DIVORCE_MEDIATION,
        ResourceInterface::COVERAGE_FAMILY_LAW,
        ResourceInterface::COVERAGE_WORK,
        ResourceInterface::COVERAGE_MEDICAL,
        ResourceInterface::COVERAGE_NEIGHBOUR_DISPUTES,
        ResourceInterface::COVERAGE_TAXES_AND_CAPITAL,
        ResourceInterface::COVERAGE_HOUSING_OWNED_HOUSE,
        ResourceInterface::COVERAGE_HOUSING_FOR_RENT,
        ResourceInterface::COVERAGE_HOUSING_RENTED_LIVINGUNITS,
        ResourceInterface::COVERAGE_HOUSING_RENTED_WORKUNITS,
        ResourceInterface::COVERAGE_TRAFFIC_ROADVEHICLE_ACCIDENT,
        ResourceInterface::COVERAGE_TRAFFIC_ROADVEHICLE_OTHER,
        ResourceInterface::COVERAGE_TRAFFIC_WATERVEHICLE_ACCIDENT,
        ResourceInterface::COVERAGE_TRAFFIC_WATERVEHICLE_OTHER,
        ResourceInterface::COVERAGE_HOUSING_VACATIONHOME_NL,
        ResourceInterface::COVERAGE_HOUSING_VACATIONHOME_OTHER,

        ResourceInterface::PRICE_INSURE_INCOME,
        ResourceInterface::PRICE_INSURE_CONSUMER,
        ResourceInterface::PRICE_INSURE_TRAFFIC,
        ResourceInterface::PRICE_INSURE_HOUSING,
        ResourceInterface::PRICE_INSURE_DIVORCE_MEDIATION,
        ResourceInterface::PRICE_INSURE_FAMILY_LAW,
        ResourceInterface::PRICE_INSURE_WORK,
        ResourceInterface::PRICE_INSURE_MEDICAL,
        ResourceInterface::PRICE_INSURE_NEIGHBOUR_DISPUTES,
        ResourceInterface::PRICE_INSURE_HOUSING_OWNED_HOUSE,
        ResourceInterface::PRICE_INSURE_TAXES_AND_CAPITAL,
        ResourceInterface::PRICE_INSURE_HOUSING_FOR_RENT,
        ResourceInterface::PRICE_INSURE_HOUSING_RENTED_LIVINGUNITS,
        ResourceInterface::PRICE_INSURE_HOUSING_RENTED_WORKUNITS,
        ResourceInterface::PRICE_INSURE_TRAFFIC_ROADVEHICLE_ACCIDENT,
        ResourceInterface::PRICE_INSURE_TRAFFIC_ROADVEHICLE_OTHER,
        ResourceInterface::PRICE_INSURE_TRAFFIC_WATERVEHICLE_ACCIDENT,
        ResourceInterface::PRICE_INSURE_TRAFFIC_WATERVEHICLE_OTHER,
        ResourceInterface::PRICE_INSURE_TRAFFIC_TRAFFIC_WATERVEHICLE_OTHER,
        ResourceInterface::PRICE_INSURE_HOUSING_VACATIONHOME_NL,
        ResourceInterface::PRICE_INSURE_HOUSING_VACATIONHOME_OTHER,

        ResourceInterface::REMARK_DIVORCE_MEDIATION,
        ResourceInterface::REMARK_FAMILY_LAW,
        ResourceInterface::REMARK_WORK,
        ResourceInterface::REMARK_MEDICAL,
        ResourceInterface::REMARK_NEIGHBOUR_DISPUTES,
        ResourceInterface::REMARK_TAXES_AND_CAPITAL,
        ResourceInterface::REMARK_HOUSING_OWNED_HOUSE,
        ResourceInterface::REMARK_HOUSING_FOR_RENT,
        ResourceInterface::REMARK_HOUSING_RENTED_LIVINGUNITS,
        ResourceInterface::REMARK_HOUSING_RENTED_WORKUNITS,
        ResourceInterface::REMARK_TRAFFIC_ROADVEHICLE_ACCIDENT,
        ResourceInterface::REMARK_TRAFFIC_ROADVEHICLE_OTHER,
        ResourceInterface::REMARK_TRAFFIC_WATERVEHICLE_ACCIDENT,
        ResourceInterface::REMARK_TRAFFIC_WATERVEHICLE_OTHER,
        ResourceInterface::REMARK_HOUSING_VACATIONHOME,
        ResourceInterface::REMARK_HOUSING_VACATIONHOME_NL,
        ResourceInterface::REMARK_HOUSING_VACATIONHOME_OTHER,

        ResourceInterface::REMARK_PRICE_SURCHARGES,

        ResourceInterface::RESOURCE_PREMIUM_EXTENDED_ID,
    ];

    // We have a local mapping, because these are mapped to [COVERAGE]_VALUE in the old Premium.
    protected $localResultMapping = [
        'PD_CONSUMENT'                  => ResourceInterface::PRICE_INSURE_CONSUMER,
        'PD_INKOMEN'                    => ResourceInterface::PRICE_INSURE_INCOME,
        'PD_VERKEER'                    => ResourceInterface::PRICE_INSURE_TRAFFIC,
        'PD_WONEN'                      => ResourceInterface::PRICE_INSURE_HOUSING,

        'PD_SCHEIDINGSMEDIATION'        => ResourceInterface::PRICE_INSURE_DIVORCE_MEDIATION,
        'PD_PERSONEN_EN_FAMILIERECHT'   => ResourceInterface::PRICE_INSURE_FAMILY_LAW,
        'PD_ARBEID'                     => ResourceInterface::PRICE_INSURE_WORK,
        'PD_MEDISCH'                    => ResourceInterface::PRICE_INSURE_MEDICAL,
        'PD_BURENRECHT'                 => ResourceInterface::PRICE_INSURE_NEIGHBOUR_DISPUTES,
        'PD_FISCAAL_EN_VERMOGEN'        => ResourceInterface::PRICE_INSURE_TAXES_AND_CAPITAL,
        'PD_EIGENWONING'                => ResourceInterface::PRICE_INSURE_HOUSING_OWNED_HOUSE,
        'PD_VERH_EIGENWONING'           => ResourceInterface::PRICE_INSURE_HOUSING_FOR_RENT,
        'PD_VERH_WOONEENH'              => ResourceInterface::PRICE_INSURE_HOUSING_RENTED_LIVINGUNITS,
        'PD_VERH_BEDREENH'              => ResourceInterface::PRICE_INSURE_HOUSING_RENTED_WORKUNITS,
        'PD_MOTORRIJTUIG_ONGEVAL'       => ResourceInterface::PRICE_INSURE_TRAFFIC_ROADVEHICLE_ACCIDENT,
        'PD_MOTORRIJTUIG_OVERIG'        => ResourceInterface::PRICE_INSURE_TRAFFIC_ROADVEHICLE_OTHER,
        'PD_PLEZIERVAARTUIG_ONGEVAL'    => ResourceInterface::PRICE_INSURE_TRAFFIC_WATERVEHICLE_ACCIDENT,
        'PD_PLEZIERVAARTUIG_OVERIG'     => ResourceInterface::PRICE_INSURE_TRAFFIC_WATERVEHICLE_OTHER,
        'PD_VAKWONING_NL'               => ResourceInterface::PRICE_INSURE_HOUSING_VACATIONHOME_NL,
        'PD_VAKWONING_BUITENL'          => ResourceInterface::PRICE_INSURE_HOUSING_VACATIONHOME_OTHER,
    ];

    protected $periodToText = [
        '1' => 'Maand',
        '12' => 'Jaar',
    ];

    // Input names
    protected $coverageToExternalCoverage = [
        ResourceInterface::INSURE_CONSUMER => 'Verzekering_Consument',
        ResourceInterface::INSURE_FAMILY_LAW => 'Verzekering_Personen_en_Familierecht',
        ResourceInterface::INSURE_INCOME => 'Verzekering_Inkomen',
        ResourceInterface::INSURE_WORK => 'Verzekering_Arbeid',
        ResourceInterface::INSURE_TAXES_AND_CAPITAL => 'Verzekering_Fiscaal_en_Vermogen',
        ResourceInterface::INSURE_DIVORCE_MEDIATION => 'Verzekering_ScheidingsMediation',
        ResourceInterface::INSURE_MEDICAL => 'Verzekering_Medisch',
        ResourceInterface::INSURE_HOUSING => 'Verzekering_Wonen',
        ResourceInterface::INSURE_NEIGHBOUR_DISPUTES => 'Verzekering_Burenrecht',
        ResourceInterface::INSURE_TRAFFIC => 'Verzekering_Verkeer',
        ResourceInterface::INSURE_TRAFFIC_ROADVEHICLE_ACCIDENT => 'Verzekering_Motorvoertuigen_Ongeval',
        ResourceInterface::INSURE_TRAFFIC_ROADVEHICLE_OTHER => 'Verzekering_Motorvoertuigen_Overig',
        ResourceInterface::INSURE_TRAFFIC_WATERVEHICLE_ACCIDENT => 'Verzekering_Vaartuigen_Ongeval',
        ResourceInterface::INSURE_TRAFFIC_WATERVEHICLE_OTHER => 'Verzekering_Vaartuigen_Overig',
    ];

    // Output names
    protected $coverageNames = [
        'DEKKING_CONSUMENT'         => ResourceInterface::COVERAGE_CONSUMER,
        'DEKKING_INKOMEN'           => ResourceInterface::COVERAGE_INCOME,
        'DEKKING_VERKEER'           => ResourceInterface::COVERAGE_TRAFFIC,
        'DEKKING_WONEN'             => ResourceInterface::COVERAGE_HOUSING,

        'DEKKING_SCHEIDINGSMEDIATION'     => ResourceInterface::COVERAGE_DIVORCE_MEDIATION,
        'DEKKING_FISCAAL_EN_VERMOGEN'     => ResourceInterface::COVERAGE_TAXES_AND_CAPITAL,
        'DEKKING_PERSONEN_EN_FAMILIERECHT' => ResourceInterface::COVERAGE_FAMILY_LAW,
        'DEKKING_ARBEID'                => ResourceInterface::COVERAGE_WORK,
        'DEKKING_MEDISCH'               => ResourceInterface::COVERAGE_MEDICAL,
        'DEKKING_BURENRECHT'            => ResourceInterface::COVERAGE_NEIGHBOUR_DISPUTES,
        'DEKKING_EIGENWONING'           => ResourceInterface::COVERAGE_HOUSING_OWNED_HOUSE,
        'DEKKING_VERH_EIGENWONING'      => ResourceInterface::COVERAGE_HOUSING_FOR_RENT,
        'DEKKING_VERH_WOONEENH'         => ResourceInterface::COVERAGE_HOUSING_RENTED_LIVINGUNITS,
        'DEKKING_VERH_BEDREENH'         => ResourceInterface::COVERAGE_HOUSING_RENTED_WORKUNITS,
        'DEKKING_MOTORRIJTUIG_ONGEVAL'  => ResourceInterface::COVERAGE_TRAFFIC_ROADVEHICLE_ACCIDENT,
        'DEKKING_MOTORRIJTUIG_OVERIG'   => ResourceInterface::COVERAGE_TRAFFIC_ROADVEHICLE_OTHER,
        'DEKKING_PLEZIERVAARTUIG_ONGEVAL' => ResourceInterface::COVERAGE_TRAFFIC_WATERVEHICLE_ACCIDENT,
        'DEKKING_PLEZIERVAARTUIG_OVERIG' => ResourceInterface::COVERAGE_TRAFFIC_WATERVEHICLE_OTHER,
        'DEKKING_VAKWONING_NL'          => ResourceInterface::COVERAGE_HOUSING_VACATIONHOME_NL,
        'DEKKING_VAKWONING_BUITENL'     => ResourceInterface::COVERAGE_HOUSING_VACATIONHOME_OTHER,
    ];

    protected $requestedInsuredAmount = 0;
    protected $requestedPaymentPeriod = 1;
    protected $requestedOwnRisk = 0;
    protected $requestedFranchise = 0;
    protected $selectedCoverages = [];


    public function __construct()
    {
        parent::__construct('RECHTSBIJSTAND', self::TASK_PROCESS_TWO);
        $this->strictStandardFields = false;
        $this->choiceLists          = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams        = [
            self::BEREKENING_MY_KEY => ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.code')),
        ];
    }

    public function setParams(Array $params)
    {
        $this->requestedInsuredAmount = (int)$params[ResourceInterface::CALCULATION_INSURED_AMOUNT];
        $this->requestedPaymentPeriod = (int)$params[ResourceInterface::PAYMENT_PERIOD];
        $this->requestedOwnRisk = (int)$params[ResourceInterface::CALCULATION_OWN_RISK];
        $this->requestedFranchise = (int)$params[ResourceInterface::CALCULATION_FRANCHISE];

        $selectedCoverages = $params[ResourceInterface::SELECTED_COVERAGES];
        if (!is_array($selectedCoverages))
            $selectedCoverages = explode(',', (string)$params[ResourceInterface::SELECTED_COVERAGES]);

        $this->selectedCoverages = $selectedCoverages;

        $postalCodeNumbers = substr($params[ResourceInterface::POSTAL_CODE], 0, 4);
        $postalCodeChars   = substr(str_replace(' ', '', $params[ResourceInterface::POSTAL_CODE]), 4, 2);

        $serviceParams = [];
        foreach ($this->coverageToExternalCoverage as $inputName => $externalName)
        {
            $serviceParams[$externalName] = in_array($inputName, $selectedCoverages) || in_array(str_replace('insure_', '', $inputName), $selectedCoverages) ? 'ja' : 'nee';
        }

        $serviceParams += [
            'Berekening_Ingangsdatum' => $params[ResourceInterface::START_DATE] == '' ? date('Ymd') : $params[ResourceInterface::START_DATE],
            'Persoon_Geboortedatum' => $params[ResourceInterface::BIRTHDATE],
            'Persoonlijke_Omstandigheden' => $params[ResourceInterface::PERSON_SINGLE],
            'Persoon_Postcode' => $postalCodeNumbers,
            'Persoon_Pc_Letters' => $postalCodeChars,
            'Berekening_Verzekerd_Bedrag' => $params[ResourceInterface::CALCULATION_INSURED_AMOUNT],
            'Berekening_ER' => $params[ResourceInterface::CALCULATION_OWN_RISK],
            'Berekening_Franchise' => $params[ResourceInterface::CALCULATION_FRANCHISE],
            'Persoon_Eigenwoning' => $params[ResourceInterface::HOUSE_OWNER],
            'Persoon_Verhuur_Eigenwoning' => $params[ResourceInterface::IS_HOUSE_FOR_RENT],
            'Persoon_Verhuur_Wooneenheden' => $params[ResourceInterface::HOUSE_RENTED_LIVINGUNITS],
            'Persoon_Verhuur_Bedrijfseenheden' => $params[ResourceInterface::HOUSE_RENTED_WORKUNITS],
            'VakantieWoning_Locatie' => $params[ResourceInterface::VACATIONHOME_LOCATION],
            //'Vaartuig_Cataloguswaarde' => $params[ResourceInterface::PRICE_WATERVEHICLE_CATALOGUS],
            'Berekening_Betalingstermijn' => isset($this->periodToText[$params[ResourceInterface::PAYMENT_PERIOD]]) ? $this->periodToText[$params[ResourceInterface::PAYMENT_PERIOD]] : 'Maand',
            'Berekening_Nulpremies' => $params[ResourceInterface::RETURN_ALL_PRODUCTS],
            'Sep_Num_Char' => ',.',
        ];

        parent::setParams($serviceParams);
    }

    /**
     * @param $params
     *
     * @return string
     */
    protected function setDefault($key, $params, $default)
    {
        return isset($params[$key]) ? $params[$key] : $default;
    }

    public function getResult()
    {
        $result = parent::getResult();

        $filteredResults = [];
        foreach ($result as $row)
        {
            // We have an error...
            // TODO: handle the error (usually is 'GEEN DEKKINGEN GEVRAAGD') on no input
            if (isset($row['GLOBAL_ESCAPE_REASON']) && $row['GLOBAL_ESCAPE_REASON'] !== '')
                continue;

            // The MoneyView API does not strictly filter some fields, returning many that are 'around' the requested value.
            // We do stricter 'requested or better (for the consumer)' filtering here.
            if ((int)$row['VERZEKERDBEDRAG'] < $this->requestedInsuredAmount)
                continue;
            if ((int)$row['EIGENRISICO'] > $this->requestedOwnRisk)
                continue;
            if ((int)$row['FRANCHISE'] > $this->requestedFranchise)
                continue;

            $coverageNameFlip = array_flip($this->coverageNames);

            // Map PD_ results here, because the global result mapping gets it wrong
            // (because they are for the normal premium service)
            foreach ($this->localResultMapping as $from => $to)
            {
                $name = str_replace('price_insure_', '', $to);
                $covered = isset($coverageNameFlip['coverage_'. $name], $row[$coverageNameFlip['coverage_'. $name]]) && $row[$coverageNameFlip['coverage_'. $name]] == 'JA';

                if (isset($row[$from]))
                {
                    $row[ResourceInterface::COVERAGES][] = [
                        'name' => $name,
                        'title' => str_replace('PD_', '', $from),
                        'price' => (float)$row[$from],
                        'remark' => isset($row['OPM_'. str_replace('PD_', '', $from)]) ? $row['OPM_'. str_replace('PD_', '', $from)] : null,
                        'is_available' => true,
                        'is_selected' => in_array($name, $this->selectedCoverages) || in_array('insure_'. $name, $this->selectedCoverages),
                        'is_covered' => $covered,
                    ];

                    $row[$to] = $row[$from];
                    unset($row[$from]);
                }
                else
                {
                    $row[ResourceInterface::COVERAGES][] = [
                        'name' => $name,
                        'title' => str_replace('PD_', '', $from),
                        'price' => null,
                        'is_available' => null,
                        'is_selected' => false,
                        'is_covered' => $covered,
                    ];
                }
            }

            // There is no 'unique' id or code, so we use the insurer name + product name
            $row[ResourceInterface::RESOURCE_PREMIUM_EXTENDED_ID] = $row['LOCAL'] .' '. $row['SPECIFIC'];

            // Recalculate coverage prices to be per period and incl taxes.
            // (by default always returned yearly and excl taxes)
            $divideBy = (12/$this->requestedPaymentPeriod);
            foreach ($row as $key => $value)
            {
                if (starts_with($key, 'price_insure_'))
                {
                    $row[$key] = ((float)$value / $divideBy) * (1+($row['ASSU_PERC']/100));
                }
            }
            $row['SUB_PD'] = ((float)$row['SUB_PD'] / $divideBy) * (1+($row['ASSU_PERC']/100));
            $row['ASSU_BEL'] = ((float)$row['ASSU_BEL'] / $divideBy);

            $filteredResults[] = $row;
        }

        //dd($filteredResults);

        return $filteredResults;
    }
}
