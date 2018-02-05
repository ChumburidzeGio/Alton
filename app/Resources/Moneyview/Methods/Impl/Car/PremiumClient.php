<?php

namespace App\Resources\Moneyview\Methods\Impl\Car;

use App\Helpers\ResourceFilterHelper;
use App\Interfaces\ResourceInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PremiumClient extends CarInsuranceAbstractClient
{
    protected $arguments = [
        ResourceInterface::START_DATE => [
            'rules'   => 'date | string',
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'default' => '',
            'description' => 'Berekening_Ingangsdatum - Op welke datum moet de verzekering ingaan',
        ],
        // Car data input
        ResourceInterface::VALUE_ACCESSOIRES => [
            'rules'   => 'number',
            'default' => 0,
            'example' => '12345.1234',
            'description' => 'Autogegevens_Accessoires - Gewenste bedrag voor accessoire dekking',
        ],
        ResourceInterface::VALUE_AUDIO => [
            'rules'   => 'number',
            'example' => '12345.1234',
            'default' => 0,
            'description' => 'Autogegevens_Audioapparatuur - Gewenste bedrag voor audio dekking',
        ],
        ResourceInterface::SECURITY => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'carinsurance.moneyview',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::SECURITY_CLASS,
                ],
                'field'    => ResourceInterface::SPEC_NAME,
            ],
            'default' => '',
            'description' => 'Autogegevens_Beveiliging - [Geen], [SCM1], … , [SCM5] (lookup)',

        ],
        ResourceInterface::CONSTRUCTION_DATE => [
            'rules'   => 'date',
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'default' => '',
            'description' => 'Autogegevens_Bouwjaar - Bouwdatum voertuig',
        ],
        ResourceInterface::FUEL_TYPE_NAME => [
            'rules' => 'string',
            'default' => '',
            'description' => 'Autogegevens_Brandstof - Benzine, Diesel, ….,  LPG',
        ],
        ResourceInterface::BRAND_NAME => [
            'rules'   => 'string',
            'example' => 'Audi',
            'default' => '',
            'description' => 'Autogegevens_Merk - Merk uit de Eurotax database',
        ],
        ResourceInterface::MODEL_NAME => [
            'rules'   => 'string',
            'example' => 'AA',
            'default' => '',
            'description' => 'Autogegevens_Type - Model uit de Eurotax database',
        ],
        ResourceInterface::TYPE_NAME => [
            'rules'   => 'string',
            'example' => 'Turbu XX alot',
            'default' => '',
            'description' => 'Autogegevens_Uitvoering - Type uit Eurotax database',
        ],
        ResourceInterface::REPLACEMENT_VALUE => [
            'rules'   => 'number',
            'example' => '9999.9999',
            'default' => '',
            'description' => 'Autogegevens_Cataloguswaarde - Bedrag in Euro’s',
        ],
        ResourceInterface::DAILY_VALUE => [
            'rules'   => 'number',
            'example' => '11000',
            'default' => '',
            'filter'  => 'filterNumber',
            'description' => 'Autogegevens_Dagwaarde - Bedrag in Euro’s',
        ],
        ResourceInterface::WEIGHT => [
            'rules'   => 'number',
            'example' => '12500',
            'default' => '',
            'filter'  => 'filterNumber',
            'description' => 'Autogegevens_Gewicht - Gewicht in hele kilogrammen',
        ],
        ResourceInterface::BODY_TYPE => [
            'rules'         => self::VALIDATION_EXTERNAL_LIST,
            'external_list' => [
                'resource' => 'carinsurance.moneyview',
                'method'   => 'list',
                'params'   => [
                    'list' => ResourceInterface::BODY_TYPE,
                ],
                'field'    => ResourceInterface::SPEC_NAME
            ],
            'default' => '',
            'description' => 'Autogegevens_Carrosserievorm - Carrosserievorm uit Eurotax database (lookup)',
        ],
        ResourceInterface::POWER => [
            'rules'   => 'number',
            'example' => '1233',
            'default' => '',
            'description' => 'Autogegevens_Vermogenkw - Getal in Kilowattage',
        ],
        ResourceInterface::CYLINDER_VOLUME => [
            'rules'   => 'string',
            'example' => '12500',
            'default' => '',
            'filter'  => 'filterNumber',
            'description' => 'Autogegevens_Cc - Getal (b.v. 1245)',
        ],
        ResourceInterface::ACCELERATION => [
            'rules'   => 'string',
            'example' => '123.45',
            'default' => '',
            'description' => 'Autogegevens_Acceleratie - Getal in seconden. (let op: gebruik . als decimaal!)',
        ],
        ResourceInterface::PRICE_VAT => [
            'rules'   => 'bool',
            'example' => '',
            'default' => false,
            'description' => 'Autogegevens_Exclusief_Btw - [Ja]/[Nee] (Is de Catwaarde Exclusief BTW?)',
        ],
        ResourceInterface::LICENSEPLATE => [
            'rules'   => 'string',
            'default' => '',
            'description' => 'Autogegevens_Kenteken - Meegeven t.b.v. een grijskenteken controle',
        ],
        ResourceInterface::TURBO => [
            'rules'   => 'string',
            'default' => '',
            'description' => 'Autogegevens_Turbo - Ja, Nee, Turbo, of leeg uit Eurotax database (lookup)',
        ],
        ResourceInterface::TRANSMISSION_TYPE => [
            'rules'   => 'string',
            'default' => '',
            'description' => 'Autogegevens_Transmissie - Soort transmissie uit Eurotax database (lookup)',
        ],
        ResourceInterface::AMOUNT_OF_DOORS => [
            'rules'   => 'integer',
            'default' => '',
            'description' => 'Autogegevens_Deuren - getal tussen 0 –9 (mag ook leeg)',
        ],
        ResourceInterface::DRIVE_TYPE => [
            'rules'   => 'string',
            'default' => '',
            'description' => 'Autogegevens_Aandrijving - Voor, Achter of 4WD (lookup)',
        ],
        ResourceInterface::COLOR => [
            'rules'   => 'string',
            'default' => '',
            'description' => 'Autogegevens_Kleur - De kleur van de auto (keuze lijst volgt nog)',
        ],

        // Usage / calculation

        ResourceInterface::MILEAGE => [
            'rules'   => 'string | required',
            'description' => 'Berekening_Kilometrage - [ONBEPERKT], getal tussen 0 – 99999 (lookup)',
        ],
        ResourceInterface::BUSINESS => [
            'rules'   => 'bool',
            'default' => false,
            'description' => 'Berekening_Autogebruik - [Particulier], [Zakelijk]',
        ],
        ResourceInterface::COMPANY_ACTIVITY => [
            'rules'   => 'string',
            'default' => 'Overig',
            'description' => 'Berekening_Beroep - Lookup lijst',
        ],
        ResourceInterface::COMPANY_CAR_LEASE => [
            'rules'   => 'bool',
            'default' => false,
            'description' => 'Berekening_Categorie - [lease/bedrijfsauto] of [eigen auto] (lookup)',
        ],
        ResourceInterface::CALCULATION_OWN_RISK => [
            'rules'   => 'string',
            'default' => 'Standaard',
            'description' => 'Berekening_Er - [Standaard] of getal (lookup)',
        ],
        ResourceInterface::OWN_RISK_APPROXIMATION => [
            'rules'   => 'string',
            'default' => 'Close',
            'description' => 'Berekening_Er_Afwijking_Type - [Close] of [Equal] (bij Close mag het ER afwijken)',
        ],
        // Personal data

        ResourceInterface::BIRTHDATE => [
            'rules'   => self::VALIDATION_DATE,
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'default' => '19700101',
            'description' => 'Persoon_Geboortedatum - Geboortedatum verzekerde',
        ],
        ResourceInterface::GENDER => [
            'rules'   => 'string',
            'default' => '',
            'description' => 'Persoon_Geslacht - [Man] of [Vrouw] (lookup)',
        ],
        ResourceInterface::POSTAL_CODE            => [
            'rules'   => 'postalcode | required',
            'example' => '8014EH',
            'filter'  => 'filterToUppercase',
            'description' => 'Persoon_Postcode - Getal 1000 – 9999 | Persoon_Pc_Letters - 2 letters AA – ZZ',
        ],
        ResourceInterface::HOUSE_NUMBER           => [
            'rules'   => 'required | integer',
            'example' => '21',
            'description' => 'Persoon_Huisnr - Leeg, Getal (Op moment van schrijven 1 – 99137)',
        ],
        ResourceInterface::HOUSE_NUMBER_SUFFIX => [
            'rules'   => 'string',
            'example' => '',
            'default' => '',
            'description' => 'Persoon_Huisnrtvg - Leeg, of de toevoeging. Lijst is beschikbaar.',
        ],
        ResourceInterface::NO_CLAIM_DECLARATION => [
            'rules'   => 'bool',
            'example' => '',
            'default' => true,
            'description' => 'Specifiek_Bm_Verklaring - [Ja] of [Nee] Heeft verzekerde een BM verklaring?',
        ],
        ResourceInterface::DRIVERS_LICENSE_AGE => [
            'rules'   => 'number',
            'example' => '',
            'default' => 0,
            'description' => 'Specifiek_Jaren_Rijbewijs - Hoeveel jaar heeft verzekerde een rijbewijs?',
        ],
        ResourceInterface::CAR_MOTOR_VEHICLE_DAMAGE => [
            'rules'   => 'bool',
            'example' => '',
            'default' => false,
            'description' => 'Specifiek_Schade_Gehad - Heeft verzekerde schade gehad in afgelopen 5 jaar? (Nee/Ja)',
        ],
        ResourceInterface::YEARS_WITHOUT_DAMAGE => [
            'rules'   => 'number',
            'example' => '',
            'default' => 0,
            'description' => 'Specifiek_Schadevrije_Jaren - Hoeveel schadevrije jaren heeft verzekerde?',
        ],
        // Berekening_Tredebescherming - String - Default:nee -[Ja], [Nee], [alles] Zie toelichting
        ResourceInterface::COVERAGE => [
            'rules'   => 'array', // array or string, but can't have them both in here
            'example' => '',
            'default' => 'all',
            'description' => 'Berekening_Vorm - [WA], [BC], [VC] of [Alles] zie toelichting.',
        ],
        ResourceInterface::PAYMENT_PERIOD => [
            'rules'   => 'number',
            'example' => '',
            'default' => 1,
            'description' => 'Berekening_Betalingstermijn - [Jaar], [Half jaar], [Kwartaal], [Maand] (lookup)',
        ],
        ResourceInterface::RESOURCE => [
            'rules'   => 'array',
            'example' => '',
            'default' => [],
            'description' => 'Which resources we\'re calling for.',
        ],
        // Berekening_Assu_Belast - String - Ja - [Ja] of [Nee]
        // Berekening_Nulpremies - String - Nee - [Ja] of [Nee] (toon niet geaccepteerde producten)


        // Specifiek_Bm_Percentage - Fixed 14.4 - Niet van toepassing
        // Specifiek_Huidige_Maatschappij - String - Niet van toepassing
        // Specifiek_Huidige_Product - String - Niet van toepassing
        // Specifiek_Huidige_Vorm - String - Niet van toepassing
        // Specifiek_Trede - Int - Niet van toepassing

        // Sep_Num_Char - String - Altijd vullen met [,.] mag ook weglaten
        // Berekening_My - String - Klant identifier
        // Uitvoer_Top_Aantal - String - default: Alles - [Alles] geeft alle resultaten weer, [getal n] geeft de n laagste premies per vorm terug.
    ];

    protected $outputFields = null;

    protected $cacheDays = 1;
    protected $choiceLists;

    protected $inputParams;
    protected $inputPaymentPeriod;
    protected $inputCoverage;
    protected $serviceParams;

    protected $allowedInsurers = [];
    protected $stripAdvisorAndCollectiveResults = false;

    protected $carDataUsage = [
        'rolls_basic' => true,
        'rolls_premium' => false,
        'rdw' => true,
        'rdw_fuel' => true,
    ];

    public function __construct()
    {
        $this->allowedInsurers = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.carinsuranceFilter'));

        parent::__construct('auto', self::TASK_PROCESS_TWO, is_array($this->allowedInsurers) ? implode(',', (array)$this->allowedInsurers) : '');
        $this->documentRequest = true;
        $this->choiceLists     = ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.choicelist'));
        $this->defaultParams   = [
            self::BEREKENING_MY_KEY          => ((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.settings.code')),
            self::ASSUR_TAX_KEY              => self::ASSUR_TAX,
        ];
    }

    public function setParams(Array $params)
    {
        $postalCodeNumbers = substr($params[ResourceInterface::POSTAL_CODE], 0, 4);
        $postalCodeChars   = substr(str_replace(' ', '', $params[ResourceInterface::POSTAL_CODE]), 4, 2);

        $this->inputPaymentPeriod = $params[ResourceInterface::PAYMENT_PERIOD];
        $this->inputCoverage = (array)$params[ResourceInterface::COVERAGE];

        if (count($this->inputCoverage) != 1)
            $params[ResourceInterface::COVERAGE] = 'all';
        else
            $params[ResourceInterface::COVERAGE] = head($this->inputCoverage);

        if (!empty($params[ResourceInterface::LICENSEPLATE]) && empty($params[ResourceInterface::BRAND_NAME]))
        {

            if ($this->carDataUsage['rdw'] && !$this->hasErrors())
                $params = $this->fetchCarRdwData($params);              // For: body_type, amount_of_doors, cylinder_volume, weight
            if ($this->carDataUsage['rdw_fuel'] && !$this->hasErrors())
                $params = $this->fetchCarRdwEngineData($params);    // For: fuel_type, power
            if ($this->carDataUsage['rolls_basic'] && !$this->hasErrors())
                $params = $this->fetchCarRollsBasicData($params);        // For: brand_name, model_name, type_name, catalogus_value, daily_value,
            if ($this->carDataUsage['rolls_premium'] && !$this->hasErrors())
                $params = $this->fetchCarRollsPremiumData($params);  // For: all data
        }

        if (empty($params[ResourceInterface::START_DATE]))
            $params[ResourceInterface::START_DATE] = date('Y-m-d');

        if (isset($this->genderToExternalCode[$params[ResourceInterface::GENDER]]))
            $params[ResourceInterface::GENDER] = $this->genderToExternalCode[$params[ResourceInterface::GENDER]];

        // We maximize the security class for now, to get maximum results
        $params[ResourceInterface::SECURITY] = 'scm5';

        $this->inputParams = $params;

        $serviceParams = [
            'Berekening_Ingangsdatum' => empty($params[ResourceInterface::START_DATE]) ? date('Ymd') : date('Ymd', strtotime($params[ResourceInterface::START_DATE])),
            'Autogegevens_Accessoires' => $params[ResourceInterface::VALUE_ACCESSOIRES],
            'Autogegevens_Audioapparatuur' => $params[ResourceInterface::VALUE_AUDIO],
            'Autogegevens_Beveiliging' => $params[ResourceInterface::SECURITY],
            'Autogegevens_Bouwjaar' => !empty($params[ResourceInterface::CONSTRUCTION_DATE]) ? date('Ymd', strtotime($params[ResourceInterface::CONSTRUCTION_DATE])) : '',
            'Autogegevens_Brandstof' => $params[ResourceInterface::FUEL_TYPE_NAME],
            'Autogegevens_Merk' => $params[ResourceInterface::BRAND_NAME],
            'Autogegevens_Type' => $params[ResourceInterface::MODEL_NAME],
            'Autogegevens_Uitvoering' => $params[ResourceInterface::TYPE_NAME],
            'Autogegevens_Cataloguswaarde' => $params[ResourceInterface::REPLACEMENT_VALUE],
            'Autogegevens_Dagwaarde' => $params[ResourceInterface::DAILY_VALUE],
            'Autogegevens_Gewicht' => $params[ResourceInterface::WEIGHT],
            'Autogegevens_Carrosserievorm' => $params[ResourceInterface::BODY_TYPE],
            'Autogegevens_Vermogenkw' => $params[ResourceInterface::POWER],
            'Autogegevens_Cc' => $params[ResourceInterface::CYLINDER_VOLUME],
            'Autogegevens_Acceleratie' => $params[ResourceInterface::ACCELERATION],
            'Autogegevens_Exclusief_Btw' => 'Nee',
            'Autogegevens_Kenteken' => $params[ResourceInterface::LICENSEPLATE],
            'Autogegevens_Tweede_Gezinsauto' => 'Nee',
            'Autogegevens_Turbo' => isset($params[ResourceInterface::TURBO]) ? ($params[ResourceInterface::TURBO] ? 'Ja' : 'Nee') : '',
            'Autogegevens_Transmissie' => $params[ResourceInterface::TRANSMISSION_TYPE],
            'Autogegevens_Deuren' => $params[ResourceInterface::AMOUNT_OF_DOORS],
            'Autogegevens_Aandrijving' => $params[ResourceInterface::DRIVE_TYPE],
            'Autogegevens_Kleur' => $params[ResourceInterface::COLOR],
            'Berekening_Kilometrage' => $params[ResourceInterface::MILEAGE],
            'Berekening_Autogebruik' => ResourceFilterHelper::filterBooleanToInt($params[ResourceInterface::BUSINESS]) ? 'Zakelijk' : 'Particulier',
            'Berekening_Beroep' => $params[ResourceInterface::COMPANY_ACTIVITY],
            'Berekening_Categorie' => $params[ResourceInterface::COMPANY_CAR_LEASE] ? 'lease/bedrijfsauto' : 'eigen auto',
            'Berekening_Er' => $params[ResourceInterface::CALCULATION_OWN_RISK],
            'Berekening_Extra_Dekking' => '',
            'Berekening_Er_Afwijking_Type' => $params[ResourceInterface::OWN_RISK_APPROXIMATION],
            'Berekening_Er_Afwijking_Waarde' => 0,

            'Persoon_Geboortedatum' => date('Ymd', strtotime($params[ResourceInterface::BIRTHDATE])),
            'Persoon_Geslacht' => $params[ResourceInterface::GENDER],
            'Persoon_Postcode' => $postalCodeNumbers,
            'Persoon_Pc_Letters '  => $postalCodeChars,
            'Persoon_Huisnr' => $params[ResourceInterface::HOUSE_NUMBER],
            'Persoon_Huisnrtvg' => $params[ResourceInterface::HOUSE_NUMBER_SUFFIX],
            'Specifiek_Bm_Verklaring' => $params[ResourceInterface::NO_CLAIM_DECLARATION] ? 'Ja' : 'Nee',
            'Specifiek_Jaren_Rijbewijs' => $params[ResourceInterface::DRIVERS_LICENSE_AGE],
            'Specifiek_Schade_Gehad' => $params[ResourceInterface::CAR_MOTOR_VEHICLE_DAMAGE] ? 'Ja' : 'Nee',
            'Specifiek_Schadevrije_Jaren' => $params[ResourceInterface::YEARS_WITHOUT_DAMAGE],

            'Berekening_Tredebescherming' => 'Nee',
            'Berekening_Vorm' => isset($this->coverageToExternalCode[$params[ResourceInterface::COVERAGE]]) ? $this->coverageToExternalCode[$params[ResourceInterface::COVERAGE]] : 'all',
            'Berekening_Betalingstermijn' => isset($this->paymentPeriodToCode[$params[ResourceInterface::PAYMENT_PERIOD]]) ? $this->paymentPeriodToCode[$params[ResourceInterface::PAYMENT_PERIOD]] : $params[ResourceInterface::PAYMENT_PERIOD],

            'Berekening_Nulpremies' => 'Ja', // Switch this to 'Ja' to get errors back why premiums were not matched

            /*
            'Specifiek_Bm_Percentage' => $params[ResourceInterface::SOMETHING],
            'Specifiek_Huidige_Maatschappij' => $params[ResourceInterface::SOMETHING],
            'Specifiek_Huidige_Product' => $params[ResourceInterface::SOMETHING],
            'Specifiek_Huidige_Vorm' => $params[ResourceInterface::SOMETHING],
            'Specifiek_Trede' => $params[ResourceInterface::SOMETHING],
            */

            'Uitvoer_Top_Aantal' => 'Alles',
        ];

        $this->serviceParams = $serviceParams;

        parent::setParams($serviceParams);
    }

    public function getResult()
    {
        $results = parent::getResult();

        $additionalCoverages = [];

        $premiums  = [];
        foreach($results as $result){
            if (isset($result['GLOBAL_ESCAPE_REASON'], $result['LOCAL']))
            {
                if (str_contains($result['GLOBAL_ESCAPE_REASON'], 'KAN MODEL GROEP NIET BEPALEN')
                    && $this->inputParams[ResourceInterface::MODEL_NAME] != '')
                {
                    // Retry without Model name
                    $newInputParams = $this->inputParams;
                    $newInputParams[ResourceInterface::MODEL_NAME] = '';

                    Log::warning('MoneyView - Model Not Found: '. json_encode($this->inputParams));

                    $newResult = $this->internalRequest('carinsurance.moneyview', 'premium', $newInputParams, true);
                    if ($this->resultHasError($newResult))
                    {
                        Log::warning('MoneyView - Model Error Result: '. json_encode($newResult));

                        if (isset($newResult['error']))
                            $this->setErrorString('Internal request error: '. $newResult['error']);
                        if (isset($newResult['error_messages']))
                            foreach ($newResult['error_messages'] as $message)
                                $this->addErrorMessage($message['field'], $message['code'], 'Internal request error: '. $message['message'], $message['type']);
                    }
                    return $newResult;
                }

                if (count($results) == 1 && $this->debug) {
                    $this->setErrorString('MoneyView error (' . $result['LOCAL'] . '): ' . $result['GLOBAL_ESCAPE_REASON']); //  . ' - ' . print_r($this->serviceParams, true)
                    break;
                }
                continue;
            }

            // Skip if not one of the allowed insurers (MoneyView does weird wildcard matching sometimes)
            if (is_array($this->allowedInsurers) && !in_array($result['LOCAL'], $this->allowedInsurers))
                continue;

            if (isset($result['VORM'], $this->externalVormCodeToCoverage[$result['VORM']])) {
                $result['VORM'] = $this->externalVormCodeToCoverage[$result['VORM']];

                // Skip if not in a requested coverage
                if (!in_array('all', $this->inputCoverage) && !in_array($result['VORM'], $this->inputCoverage))
                    continue;
            }

            // We need to always return as month
            if (isset($result['KLANT_PREMIE']))
                $result['KLANT_PREMIE'] = $result['KLANT_PREMIE'] / $this->inputPaymentPeriod;

            // Fetch Additional Coverages, only if we're asking one specific Resource
            if (isset($this->inputParams['resource']['id']) && count($this->inputParams['resource']['id']) == 1 && in_array($result['CODE'], (array)$this->inputParams['resource']['id']) && !isset($additionalCoverages[$result['LOCAL']]))
            {
                $additionalCoverages[$result['LOCAL']] = $this->internalRequest(
                    'carinsurance.moneyview',
                    'additional_coverages',
                    $this->inputParams + [ResourceInterface::COMPANY_NAME => $result['LOCAL']],
                    true
                );
                if ($this->resultHasError($additionalCoverages[$result['LOCAL']])) {
                    $this->setErrorString('Internal coverages error: ' . json_encode($additionalCoverages[$result['LOCAL']]));
                    $additionalCoverages[$result['LOCAL']] = [];
                }
            }
            if (isset($additionalCoverages[$result['LOCAL']]))
                $result += $additionalCoverages[$result['LOCAL']];

            $premiums[] = $result;
        }

        return $premiums;
    }
}
