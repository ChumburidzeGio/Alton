<?php

namespace App\Resources\Moneyview\Methods\Impl\Car;

use App\Interfaces\ResourceInterface;
use Illuminate\Support\Facades\Config;

class AdditionalCoveragesClient extends CarInsuranceAbstractClient
{
    protected $arguments = [
        ResourceInterface::START_DATE => [
            'rules'   => 'date | string',
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'default' => '',
            'description' => 'Berekening_Ingangsdatum - Op welke datum moet de verzekering ingaan',
        ],
        ResourceInterface::LICENSEPLATE => [
            'rules'   => 'string',
            'default' => '',
            'description' => 'Can fetch body_type and amount_of_seats if given.',
        ],
        ResourceInterface::COMPANY_NAME => [
            'rules'   => 'string | required',
            'default' => '',
            'description' => 'Company name that you combine the additional coverages with.',
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
            'description' => 'Autogegevens_Carrosserievorm - Carrosserievorm uit Eurotax database (lookup)',
        ],
        ResourceInterface::AMOUNT_OF_SEATS => [
            'rules'   => 'string',
            'example' => '',
            'default' => '9',
            'description' => 'Number of car seats for coverage',
        ],
        ResourceInterface::POSTAL_CODE            => [
            'rules'   => 'postalcode | required',
            'example' => '8014EH',
            'filter'  => 'filterToUppercase',
            'description' => 'Persoon_Postcode - Getal 1000 – 9999 | Persoon_Pc_Letters - 2 letters AA – ZZ',
        ],
        ResourceInterface::BUSINESS => [
            'rules'   => 'bool',
            'default' => false,
            'description' => 'Berekening_Autogebruik - [Particulier], [Zakelijk] ',
        ],
        ResourceInterface::COMPANY_CAR_LEASE => [
            'rules'   => 'bool',
            'default' => false,
            'description' => 'Berekening_Categorie - [lease/bedrijfsauto] of [eigen auto] (lookup)',
        ],

        ResourceInterface::PASSENGER_INSURANCE_ACCIDENT => [
            'rules'   => 'bool',
            'example' => '',
            'default' => true,
            'description' => 'Oiv - string -[Ja]/[Nee] Is OIV dekking gewenst?',
        ],
        ResourceInterface::PASSENGER_INSURANCE_DAMAGE => [
            'rules'   => 'bool',
            'example' => '',
            'default' => true,
            'description' => 'Svi - string - [Ja]/[Nee]  Is SVI dekking gewenst?',
        ],
        ResourceInterface::LEGALEXPENSES => [
            'rules'   => 'bool',
            'example' => '',
            'default' => true,
            'description' => 'Svi - string - [Ja]/[Nee] ] Is Rechtsbijstanddekking gewenst?',
        ],
        ResourceInterface::ROADSIDE_ASSISTANCE => [
            'rules'   => 'bool',
            'example' => '',
            'default' => true,
            'description' => 'Hulpverlening - string - [Ja]/[Nee] ] Is dekking voor Hulpverlening gewenst?',
        ],
        ResourceInterface::YEARS_WITHOUT_DAMAGE => [
            'rules'   => 'number',
            'example' => '',
            'default' => 0,
            'description' => 'Specifiek_Schadevrije_Jaren - Hoeveel schadevrije jaren heeft verzekerde?',
        ],
        ResourceInterface::CONSTRUCTION_DATE => [
            'rules'   => 'date | string',
            'example' => '1988-11-09 (yyyy-mm-dd)',
            'default' => '',
            'description' => 'Autogegevens_Bouwjaar - Bouwdatum voertuig',
        ],
        ResourceInterface::PAYMENT_PERIOD => [
            'rules'   => 'number',
            'example' => '',
            'default' => 1,
            'description' => 'Berekening_Betalingstermijn - [Jaar], [Half jaar], [Kwartaal], [Maand] (lookup)',
        ],

        // Sep_Num_Char - String - Altijd vullen met [,.] mag ook weglaten
        // Berekening_My - String - Klant identifier
    ];

    protected $outputFields = null;

    protected $cacheDays = false; // TODO: remove after dev
    protected $choiceLists;

    protected $inputPaymentPeriod;

    protected $additionalCoverageCodeToName = [
        'HULP' => ResourceInterface::ROADSIDE_ASSISTANCE_VALUE,
        'RBS' => ResourceInterface::LEGALEXPENSES_VALUE,
        'SVI' => ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
        'OIV' => ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE,
    ];

    public function __construct()
    {
        parent::__construct('ed_auto', self::TASK_PROCESS_ONE, implode(',', (array)((app()->configure('resource_moneyview')) ? '' : config('resource_moneyview.carinsuranceFilter'))));
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

        if (empty($params[ResourceInterface::START_DATE]))
            $params[ResourceInterface::START_DATE] = date('Y-m-d');

        if (!empty($params[ResourceInterface::LICENSEPLATE]) && empty($params[ResourceInterface::BODY_TYPE]))
        {
            $params = $this->fetchCarRdwData($params);
        }

        $serviceParams = [
            'Auto_Local' => $params[ResourceInterface::COMPANY_NAME],
            'Berekening_Ingangsdatum' => empty($params[ResourceInterface::START_DATE]) ? date('Ymd') : date('Ymd', strtotime($params[ResourceInterface::START_DATE])),
            'Autogegevens_Carrosserievorm' => $params[ResourceInterface::BODY_TYPE],
            'Persoon_Postcode' => $postalCodeNumbers,
            'Persoon_Pc_Letters '  => $postalCodeChars,
            'Berekening_Autogebruik' => $params[ResourceInterface::BUSINESS] ? 'Zakelijk' : 'Particulier',
            'Berekening_Categorie' => $params[ResourceInterface::COMPANY_CAR_LEASE] ? 'lease/bedrijfsauto' : 'eigen auto',

            'Oiv' => $params[ResourceInterface::PASSENGER_INSURANCE_ACCIDENT] ? 'Ja' : 'Nee',
            'Oiv_Vb_A' => 25000, // "Verzekerd Bedrag A" -  The maximum
            'Oiv_Vb_B' => 50000, // "Verzekerd Bedrag B" - The maximum
            'Oiv_Aantal_Zitplaatsen' => $params[ResourceInterface::AMOUNT_OF_SEATS],
            'Svi' => $params[ResourceInterface::PASSENGER_INSURANCE_DAMAGE] ? 'Ja' : 'Nee',
            'Svi_Vb' => 2500000, // "Verzekerd Bedrag" - The maximum
            'Svi_Aantal_Zitplaatsen' => $params[ResourceInterface::AMOUNT_OF_SEATS],
            'Rbs' => $params[ResourceInterface::LEGALEXPENSES] ? 'Ja' : 'Nee',
            'Hulpverlening' => $params[ResourceInterface::ROADSIDE_ASSISTANCE] ? 'Ja' : 'Nee',

            'Specifiek_Schadevrije_Jaren' => $params[ResourceInterface::YEARS_WITHOUT_DAMAGE],
            'Autogegevens_Bouwjaar' =>  date('Ymd', strtotime($params[ResourceInterface::CONSTRUCTION_DATE])),
            'Berekening_Betalingstermijn' => isset($this->paymentPeriodToCode[$params[ResourceInterface::PAYMENT_PERIOD]]) ? $this->paymentPeriodToCode[$params[ResourceInterface::PAYMENT_PERIOD]] : $params[ResourceInterface::PAYMENT_PERIOD],
            'Berekening_Nulpremies' => 'Ja',
        ];

        parent::setParams($serviceParams);
    }

    public function getResult()
    {
        $results = parent::getResult();

        $coverages = [];
        foreach($results as $result) {
            if (isset($result['GLOBAL_ESCAPE_REASON']))
                continue;

            // Don't know what to do with period fixed-fee stuff here, so skip it.
            if (isset($result['POLISKOSTEN']) && (float)$result['POLISKOSTEN'] > 0)
                continue;

            if (isset($result['PREMIE']))
                $result['PREMIE'] = $result['PREMIE'] / $this->inputPaymentPeriod;

            if (isset($result['SOORT'], $result['PREMIE']) && isset($this->additionalCoverageCodeToName[$result['SOORT']]))
            {
                if ($this->additionalCoverageCodeToName[$result['SOORT']] == ResourceInterface::ROADSIDE_ASSISTANCE_VALUE
                    && str_contains($result['SPECIFIC'], 'Europa'))
                {
                    // Match Europe specific roadside assistance
                    $coverages[ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE] = (float)$result['PREMIE'];
                }
                else
                {
                    $coverages[$this->additionalCoverageCodeToName[$result['SOORT']]] = (float)$result['PREMIE'];
                }
            }
        }
        return $coverages;
    }
}
