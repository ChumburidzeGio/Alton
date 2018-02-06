<?php
namespace App\Resources\Paston\Methods;

use App\Exception\ResourceError;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Resources\Paston\PastonAbstractRequest;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class CalculatePremium extends PastonAbstractRequest
{
    const DATE_FORMAT = 'Y-m-d';

    protected $cacheDays = 1;

    protected $inputTransformations = [
        ResourceInterface::PAYMENT_PERIOD => 'convertPaymentPeriod',
        ResourceInterface::COVERAGE => 'convertCoverage',
        ResourceInterface::POSTAL_CODE => 'formatPostalCode',
        ResourceInterface::CALCULATION_OWN_RISK => 'makeGenericOwnRisk',
        ResourceInterface::SECURITY_CLASS_ID => 'convertSecurityClassId',
        ResourceInterface::NO_CLAIM => 'castToBool',
        ResourceInterface::CAR_DEPRECIATION_SCHEME => 'convertDepreciationScheme',
    ];
    protected $inputToExternalMapping = [
        // Product filters (all in GET)
        ResourceInterface::COVERAGE => 'productType',
        ResourceInterface::COMPANY_NAME => 'insurer', // multiples in GET
        ResourceInterface::LIMIT => 'top', // Show X cheapest

        // Insurance data
        ResourceInterface::START_DATE => 'calculationStartDate',
        ResourceInterface::PAYMENT_PERIOD => 'paymentTerm',
        ResourceInterface::CALCULATION_OWN_RISK => 'ownRisks',
        //ResourceInterface::ACCESSOIRES_COVERAGE_VALUE => 'insuredAmounts.0.val',
        //'_' => 'requestedCovers',
        ResourceInterface::CAR_DEPRECIATION_SCHEME => 'depreciationScheme',

        // Car data
        ResourceInterface::CONSTRUCTION_DATE => 'passengerCar.buildingYear',
        ResourceInterface::LICENSEPLATE => 'passengerCar.licensePlateNumber',
        ResourceInterface::FUEL_TYPE_ID => 'passengerCar.fuelType',
        ResourceInterface::SECURITY_CLASS_ID => 'passengerCar.securityClass',
        //ResourceInterface::INCLUDE_VAT => 'passengerCar.isVATExcluded',
        ResourceInterface::BRAND_NAME => 'passengerCar.make',
        ResourceInterface::MODEL_NAME => 'passengerCar.model',
        ResourceInterface::TYPE_NAME => 'passengerCar.type',
        ResourceInterface::COLOR => 'passengerCar.color',
        ResourceInterface::REPLACEMENT_VALUE => 'passengerCar.listPrice',
        ResourceInterface::DAILY_VALUE => 'passengerCar.currentValue',
        ResourceInterface::WEIGHT => 'passengerCar.weight',
        ResourceInterface::DRIVE_TYPE => 'passengerCar.wheelDrive',
        ResourceInterface::AMOUNT_OF_DOORS => 'passengerCar.numberOfDoors',
        ResourceInterface::TRANSMISSION_TYPE => 'passengerCar.transmission',
        ResourceInterface::CYLINDER_VOLUME => 'passengerCar.engineCC',
        ResourceInterface::BODY_TYPE => 'passengerCar.bodyWork',
        ResourceInterface::POWER => 'passengerCar.engineKW',
        ResourceInterface::ACCELERATION => 'passengerCar.accelaration',
        ResourceInterface::TURBO => 'passengerCar.isTurbo',
        ResourceInterface::CATEGORY => 'passengerCar.segment',
        ResourceInterface::AMOUNT_OF_SEATS => 'passengerCar.seats',
        //ResourceInterface::VALUE_ACCESSOIRES => 'passengerCar.accessoryAmount',
        //ResourceInterface::VALUE_AUDIO => 'passengerCar.audioEquipmentAmount',

        // Driver data
        ResourceInterface::BIRTHDATE => 'driver.dateOfBirth',
        ResourceInterface::POSTAL_CODE => 'driver.postalCode',
        ResourceInterface::HOUSE_NUMBER => 'driver.houseNumber',
        ResourceInterface::YEARS_WITHOUT_DAMAGE => 'driver.claimFreeYears',
        ResourceInterface::MILEAGE => 'driver.kilometrage',

        ResourceInterface::YEARLY_INCOME => 'driver.income',
        ResourceInterface::HOUSE_NUMBER_SUFFIX => 'driver.houseNumberAddition',
        ResourceInterface::NO_CLAIM => 'driver.isWithoutClaims',
        ResourceInterface::BIRTHDATE_PARTNER => 'driver.partnerDateOfBirth',
    ];
    protected $externalToResultMapping = [
        'insurer' => ResourceInterface::COMPANY_NAME,
        'productType' => ResourceInterface::COVERAGE,
        'productDetails' => ResourceInterface::NAME,
        'calculationStartDate' => ResourceInterface::START_DATE,
        'paymentTerm' => ResourceInterface::PAYMENT_PERIOD,
        'netPremium' => ResourceInterface::PRICE_DEFAULT,
        'policyCost' => ResourceInterface::PRICE_INITIAL,
        'kilometrage' => ResourceInterface::USED_MILEAGE,
        'covers' => [
            ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE,
            ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE,
            ResourceInterface::LEGALEXPENSES_VALUE,

            ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE,

            ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE, // Not supported
            ResourceInterface::ROADSIDE_ASSISTANCE_VALUE, // Not supported

            ResourceInterface::ACCESSOIRES_COVERAGE_VALUE,
            ResourceInterface::ACCESSOIRES_COVERAGE_AMOUNT,
        ],
        //'insuredAmounts' => [
        //    ResourceInterface::ACCIDENT_AND_DEATH_VALUE,
        //    ResourceInterface::ACCIDENT_AND_DISABLED_VALUE,
        //],
    ];
    protected $resultTransformations = [
        ResourceInterface::COVERAGE => 'convertFromCoverage',
        ResourceInterface::PAYMENT_PERIOD => 'convertFromPaymentPeriod',
        ResourceInterface::PASSENGER_INSURANCE_ACCIDENT_VALUE => 'getAdditionalCoverage',
        ResourceInterface::PASSENGER_INSURANCE_DAMAGE_VALUE => 'getAdditionalCoverage',
        ResourceInterface::LEGALEXPENSES_VALUE => 'getAdditionalCoverage',
        ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE => 'getAdditionalCoverage',
        ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE => 'getAdditionalCoverage',
        ResourceInterface::ROADSIDE_ASSISTANCE_VALUE => 'getAdditionalCoverage',
        ResourceInterface::ACCESSOIRES_COVERAGE_VALUE => 'getAdditionalCoverage',
        ResourceInterface::ACCESSOIRES_COVERAGE_AMOUNT => 'getAccessoiresAmount',
        //ResourceInterface::ACCIDENT_AND_DEATH_VALUE => 'getAccidentCoverage',
        //ResourceInterface::ACCIDENT_AND_DISABLED_VALUE => 'getAccidentCoverage',
        ResourceInterface::RESOURCE_ID => 'createResourceId',
        ResourceInterface::OWN_RISK => 'getGenericOwnRisk',
        ResourceInterface::PRICE_DEFAULT => 'convertPriceToMonth'
    ];

    protected $getQueryParams = [
        'productType',
        'insurer',
        'top',
    ];

    protected $requiredInput = [
        ResourceInterface::PAYMENT_PERIOD,
        ResourceInterface::CONSTRUCTION_DATE,
        ResourceInterface::BRAND_NAME,
        ResourceInterface::REPLACEMENT_VALUE,
        ResourceInterface::WEIGHT,
        ResourceInterface::BODY_TYPE,
        ResourceInterface::BIRTHDATE,
        ResourceInterface::POSTAL_CODE,
        ResourceInterface::YEARS_WITHOUT_DAMAGE,
        ResourceInterface::MILEAGE,
    ];

    protected $coverageToPastonId = [
        ResourceValue::CAR_COVERAGE_ALL => null,
        ResourceValue::CAR_COVERAGE_MINIMUM => 'WA',
        ResourceValue::CAR_COVERAGE_LIMITED => 'WABC',
        ResourceValue::CAR_COVERAGE_COMPLETE => 'WAVC',
    ];

    protected $additionalCoverageToPastonCoverIds = [
        ResourceInterface::ROADSIDE_ASSISTANCE => 'ROADSIDE_ASSISTANCE',
        ResourceInterface::PASSENGER_INSURANCE_ACCIDENT => 'PASSENGER_ACCIDENT',
        ResourceInterface::LEGALEXPENSES => 'LEGAL_ASSISTANCE', // TODO: Check if this is correct?
        ResourceInterface::PASSENGER_INSURANCE_DAMAGE => 'PASSENGER_DAMAGE',

        ResourceInterface::ACCESSOIRES_COVERAGE => 'ACCESSORIES',
    ];

    protected $coverageToPastonInsuredAmountTypeIds = [
        ResourceInterface::ACCESSOIRES_COVERAGE => 'ACCESSORIES',
        ResourceInterface::ACCIDENT_AND_DEATH => 'PASSENGER_ACCIDENT_DEATH',
        ResourceInterface::ACCIDENT_AND_DISABLED => 'PASSENGER_ACCIDENT_DISABLED',
        ResourceInterface::PASSENGER_INSURANCE_DAMAGE => 'PASSENGER_DAMAGE',
        ResourceInterface::GENERIC => 'GENERIC',
    ];

    protected $ownRiskTypesToPastonOwnRiskTypes = [
        ResourceValue::OWN_RISK_FREE_BODY_SHOP => 'FREE_BODY_SHOP',
        ResourceValue::OWN_RISK_GENERIC => 'GENERIC',
        ResourceValue::OWN_RISK_THEFT => 'THEFT',
    ];

    protected $depreciationTypesToPastonDepreciationTypes = [
        ResourceValue::DEPRECIATION_CURRENT_NEW_VALUE => 'CURRENT_NEW_VALUE',
        ResourceValue::DEPRECIATION_PURCHASED_VALUE => 'PURCHASED_VALUE',
        ResourceValue::DEPRECIATION_STANDARD => 'STANDARD',
    ];

    protected $fuelTypes = [
        'PETROL',
        'HYBRID_PETROL',
        'ELECTIC',
        'GASOLINE',
        'HYBRID_GASOLINE',
        'ALCOHOL',
        'LPG',
    ];

    protected $securityClasses = [
        0 => 'NONE',
        1 => 'SCM1',
        2 => 'SCM2',
        3 => 'SCM3',
        4 => 'SCM4',
        5 => 'SCM5',
    ];

    protected $wheelDrives = [
        'FRONT',
        'REAR',
        'FOUR_BY_FOUR',
    ];
    protected $carColors = [
        'CREAM',
        'GREEN',
        'BEIGE',
        'WHITE',
        'VARIOUS',
        'ORANGE',
        'RED',
        'YELLOW',
        'PINK',
        'BLUE',
        'PURPLE',
        'BLACK',
        'BROWN',
        'GREY',
    ];
    protected $transmissionTypes = [
        'STICK',
        'SEMI_AUTOMATIC',
        'AUTOMATIC',
    ];
    protected $bodyTypes = [
        'BUS',
        'MPV',
        'CABRIOLET',
        'STATIONWAGON',
        'PICK_UP',
        'ROADSTER',
        'SEDAN',
        'TERRAINWAGON',
        'COUPE',
        'HATCHBACK',
    ];
    protected $roadsideAssistanceTypes = [
        'NONE',
        'EUROPE',
        'EUROPE_WITH_ALTERNATIVE_TRANSPORT',
        'NETHERLANDS',
    ];

    protected $paymentPeriodToTermId = [
        '1' => 'MONTH',
        '6' => 'HALF_YEAR',
        '4' => 'QUARTER',
        '12' => 'YEAR',
    ];

    protected $rdwFuelToPastonFuel = [
        'Benzine' => 'PETROL',
        'Elektriciteit' => 'ELECTIC',
        'Diesel' => 'GASOLINE',
        'CNG' => 'LPG', // Fallback
        'Alcohol' => 'ALCOHOL',
        'LNG' => 'LPG',
        'LPG' => 'LPG',
        'Waterstof' => null, // Fallback
        'Elektriciteit-Benzine' => 'HYBRID_PETROL',
        'Elektriciteit-Diesel' => 'HYBRID_GASOLINE',
        // RDW not matched: 'Waterstof', 'LNG', 'CNG'
    ];
    protected $rollsBasicFuelToPastonFuel = [
        'Benzine' => 'PETROL',
        'Diesel' => 'GASOLINE',
        'Elektra' => 'ELECTIC',
        'Hybride-benzine' => 'HYBRID_PETROL',
        'Hybride / Benzine' => 'HYBRID_PETROL',
        'Hybride / Diesel' => 'HYBRID_GASOLINE',
        'Hybride / LPG' => 'HYBRID_GASOLINE', // Fallback
        'Aardgas' => 'LPG', // Fallback
        'Autogas (LPG)' => 'LPG',
        'Aardgas (CNG Compressed Natural Gas)' => 'LPG', // Fallback
        'Alcohol' => 'ALCOHOL',
        'Waterstof' => null,
        'Cryogeen' => null,
        'Biodiesel' => null,
        'Hybride' => null,
        'Olie' => null,
        'Butagas' => null,
        'Anders' => null,
    ];

    protected $rdwBodyTypeToPastonBodyType = [
        'stationwagen' => 'STATIONWAGON',
        'hatchback' => 'HATCHBACK',
        'cabriolet' => 'CABRIOLET',
        'MPV' => 'MPV',
        'sedan' => 'SEDAN',
        'coupe' => 'COUPE',
        'bus' => 'BUS',
        'pick-up truck' => 'PICK_UP',
        'terrein voertuig' => 'TERRAINWAGON',
        // Paston type not matched: 'ROADSTER'
    ];
    // Not used yet - only in Rolls Premium Licenseplate
    protected $rollsBodyTypeToPastonBodyType = [
        'Station' => 'STATIONWAGON',
        'Hatchback' => 'HATCHBACK',
        'LiftBack' => 'HATCHBACK',
        'ConvertibleHardtop' => 'CABRIOLET',
        'ConvertibleSoftTop' => 'CABRIOLET',
        'Targa' => 'CABRIOLET',
        'MPV' => 'MPV',
        'Sedan' => 'SEDAN',
        'Coupe' => 'COUPE',
        'Bus' => 'BUS',
        'PickUp' => 'PICK_UP',
        'Deliverance' => 'BUS',
        'TerrainHardTop' => 'TERRAINWAGON',
        'TerrainSofttop' => 'TERRAINWAGON',
        'Van' => 'BUS',
        'SUV' => 'MPV',
        // Rolls types not matched: 'Other', 'ChassisCabin', 'Chassis',
        // Paston type not matched: 'ROADSTER'
    ];
    protected $rollsBodyTypeIdToPastonBodyType = [
        0 => null, // 'Alle',
        10 => 'BUS', //'Bestel ',
        4 => 'CABRIOLET', //'Cabrio hardtop ',
        16 => 'CABRIOLET', //'Cabrio softtop ',
        3 => 'COUPE', //'Coupe ',
        2 => 'HATCHBACK', //'Hatchback ',
        18 => 'HATCHBACK', //'Liftback ',
        7 => 'MPV', //'MPV ',
        8 => 'BUS', //'Bus ',
        9 => 'PICK_UP', //'Pick-up ',
        1 => 'SEDAN', //'Sedan ',
        6 => 'STATIONWAGON', //'Combi ',
        15 => 'MPV', //'SUV ',
        17 => 'CABRIOLET', //'Targa ',
        14 => 'TERRAINWAGON', //'Terrein softtop ',
        5 => 'TERRAINWAGON', //'Terrein'
    ];

    protected $rdwColorToPastonColor = [
        'ORANJE' => 'ORANGE',
        'ROOD' => 'RED',
        'WIT' => 'WHITE',
        'BLAUW' => 'BLUE',
        'GROEN' => 'GREEN',
        'GEEL' => 'YELLOW',
        'GRIJS' => 'GREY',
        'BRUIN' => 'BROWN',
        'CREME' => 'CREAM',
        'PAARS' => 'PURPLE',
        'ZWART' => 'BLACK',
        'BEIGE' => 'BEIGE',
        'N.v.t.' => null,
        'Niet geregistreerd' => null,
        'ROSE' => 'PINK',
        'DIVERSEN' => 'VARIOUS',
    ];

    protected $rollsWheelDriveToPaston = [
        'V' => 'FRONT',
        'A' => 'REAR',
        'V+A' => 'FOUR_BY_FOUR', //TODO: verify if this is mapped correctly
        '4x4' => 'FOUR_BY_FOUR',
    ];

    protected $rollsTransmissionToPaston = [
        'H' => 'STICK',
        'S' => 'SEMI_AUTOMATIC',
        'A' => 'AUTOMATIC',
    ];

    protected $isaFuelTypeToPaston = [
        'A' => 'ALCOHOL', // Alcohol
        'B' => 'PETROL', // Benzine
        'C' => 'LPG', // Vloeibaar gas (Cryogeen)
        'D' => 'GASOLINE', // Diesel
        'E' => 'ELECTIC', // Electriciteit
        'G' => 'LPG', // Gas zoals LPG
        'H' => 'LPG', // Compressed Natural Gas (aardgas)
        'W' => 'ELECTIC', // Waterstof
    ];

    protected $isaTransmissionToPaston = [
        'H' => 'STICK',
        'HAND' => 'STICK',
        'A' => 'AUTOMATIC',
        'AUT' => 'AUTOMATIC',
        'SAUT' => 'SEMI-AUTOMATIC',
        'H4' => 'STICK',
        'H5' => 'STICK',
        'H6' => 'STICK',
        'H7' => 'STICK',
        'A2' => 'AUTOMATIC',
        'A3' => 'AUTOMATIC',
        'A4' => 'AUTOMATIC',
        'A5' => 'AUTOMATIC',
        'A6' => 'AUTOMATIC',
        'A7' => 'AUTOMATIC',
        'Onbekend' => '',
    ];

    protected $isaWheelDriveToPaston = [
        'VW' => 'FRONT',
        'AW' => 'REAR',
        '4' => 'FOUR_BY_FOUR',
        'VA' => 'FOUR_BY_FOUR',
        'Z' => null,
    ];

    protected $isaColorToPaston = [
        0 => 'ORANGE', // oranje
        1 => 'PINK', // rose
        2 => 'RED', // rood
        3 => 'WHITE', // wit
        4 => 'BLUE', // blauw
        5 => 'GREEN', // groen
        6 => 'YELLOW', // geel
        7 => 'GREY', // grijs
        8 => 'BROWN', // bruin
        9 => 'BEIGE', // beige
        10 => 'CREAM', // crème
        11 => 'PURPLE', // paars
        12 => 'BLACK', // zwart
        13 => 'VARIOUS', // diversen
        99 => null, // niet van toepassing
    ];

    protected $isaObjectCodeToPastonBodyType = [
        'PAB' => 'CABRIOLET', // Personenauto Cabriolet
        'PB' => 'BUS', // Personenbus
        'SE' => 'SEDAN', // Sedan
        'CO' => 'STATIONWAGON', // Combi //FIXME: This correct??
        'PAC' => 'COUPE', // Personenenauto Coupe
        'HA' => 'HATCHBACK', // Hatchback
        'MPV' => 'MPV', // Multi Purpose Vehicle
        'PI' => 'PICK_UP', // Pick-up
        'ROA' => 'ROADSTER', // Roadster
        'PAS' => 'TERRAINWAGON', // Personenauto SUV //FIXME: This correct??
        'ZZ' => null,

        // These should be mapped by service already
        'BAH' => null, // Personenauto hardtop
        'KA' => null, // Kampeerauto/Camper
        'LIM' => null, // Limousine
        'BU' => null, // Buggy
        'PAT' => null, // Personenauto softtop
        'TA' => null, // Targa
        // Non personenauto
        'BA' => null, // Bestelauto
        'AF' => null, // Personenauto + aanhanger
        'COA' => null, // Coach
        'BR' => null, // Bromfiets
        'MO' => null, // Motor
    ];
    protected $isaObjectCodeNonPersonalCars = [
        'BA' => 'Bestelauto', // Bestelauto
        'AF' => 'Personenauto + aanhanger', // Personenauto + aanhanger
        'COA' => 'Coach', // Coach
        'BR' => 'Bromfiets', // Bromfiets
        'MO' => 'Motor', // Motor
        'ZZ' => 'Onbekend',
    ];
    protected $isaSegmentIdToCategoryId = [
        '105' => 'LARGE', // "Groot"
        '104' => 'LARGE_MID', // "groot midden" = "Hogere middenklasse"??
        '109' => 'LUXE', // "Luxe"
        '111' => 'MED_MPV', // "Midi-MPV"
        '103' => 'MID', // "Middenklasse"?
        '102' => 'MID_SMALL', // "Kleine middenklasse"
        '114' => 'MIDI_SUV', // "Midi-SUV"
        '100' => 'MINI', // "Mini"
        '110' => 'MINI_MPV', // "Mini-MPV"
        '112' => 'MPV', // "MPV"
        '?'   => 'UNKNOWN', // "Midi-SUV"
        '113' => 'OPEN', // "Open, Cabrio/Roadster"
        '101' => 'SMALL', // "Klein"
        '107' => 'SPORT', // "Sportwagens"
        '106' => 'SPORTIVE', // "Sportieve modellen"
        '108' => 'SUPER_SPORT', // "Supersport"
        '116' => 'SUV', // "SUV"
        '115' => 'TERRAINWAGON', // "Terreinwagens"
    ];

    protected $filterCoverages = null;
    protected $invalidVehicleType = false;

    protected $rdwCarData = null;
    protected $rdwFuelData = null;
    protected $rollsBasicData = null;
    protected $isaCarData = null;

    protected $hasNotified = false;

    protected $carDataUsage = [
        'rolls_basic' => false,
        'rolls_premium' => false,
        'rdw' => false,
        'rdw_fuel' => false,
        'isa' => true,
    ];

    public function __construct()
    {
        parent::__construct('passenger-car/premium');
    }

    public function getDefaultParams()
    {
        return [
            'top' => 999,
            'uniqueID' => 'my-first-guid', // string (GUID?)
            'calculationStartDate' => date(self::DATE_FORMAT), // date, YYYY-MM-DD
            'originalPolicyStartDate' => null, // date, YYYY-MM-DD
            'paymentTerm' => null, // string
            'insuredAmounts' => [
                /*
                [
                    'type' => 'GENERIC', // string
                    'val' => 0, // int
                ],
                [
                    'type' => 'ACCESSOIRES', // string
                    'val' => 2000, // int
                ]
                */
            ],
            'ownRisks' => [
                /*

                [
                    'type' => 'GENERIC', // string
                    'val' => 0,   // int
                ]
*/
            ],
            'requestedCovers' => [
                'ROADSIDE_ASSISTANCE',
            ], // array of strings
            'depreciationScheme' => 'STANDARD', // string
            'passengerCar' => [
                'buildingYear' => '2000-01-01', // string, YYYY-MM-DD
                'licensePlateNumber' => null, // string
                'fuelType' => null, // string
                'securityClass' => null,  // string
                'isVATExcluded' => false, // boolean
                'make' => 'BRAND', // string
                'model' => null, // string
                'type' => null, // string
                'color' => null, // string
                'listPrice' => 0, // int
                'currentValue' => null, // int
                'weight' => 0, // int
                'wheelDrive' => null, // string
                'numberOfDoors' => null, // int
                'transmission' => null, // string
                'engineCC' => null, // int
                'bodyWork' => 'HATCHBACK', // string
                'engineKW' => null, // int
                'accelaration' => null, // int
                'isTurbo' => null, // boolean
                'seats' => 0, // Int, required for AEGON (even if 0 by default)
                //'accessoryAmount' => null, // int
                //'audioEquipmentAmount' => null, // int
            ],
            'driver' => [
                'dateOfBirth' => '1980-01-01', // string, YYYY-MM-DD
                'postalCode' => '1234 AB', // string, 'DDDD LL'
                'houseNumber' => null, // int
                'houseNumberAddition' => null, // string
                'income' => null, // int
                'partnerDateOfBirth' => null, // string, YYYY-MM-DD
                'yearsSinceDrivingLicense' => null, // int
                'claimFreeYears' => 0, // int
                'isWithoutClaims' => false, // bool
                'kilometrage' =>  0, // int
            ],
            'roadSideAssitanceType' => 'NETHERLANDS', // Hardcoded!
        ];
    }

    protected function convertPaymentPeriod($value)
    {
        return array_get($this->paymentPeriodToTermId, (string)$value, '');
    }

    protected function convertFromPaymentPeriod($value)
    {
        return array_search((string)$value, $this->paymentPeriodToTermId, true);
    }

    protected function convertCoverage($value)
    {
        if (is_array($value) && count($value) == 1)
            $value = head($value);
        else if (is_array($value)) {
            // If we do not have all coverages, filter on them
            if (count(array_unique($value)) != 3)
                $this->filterCoverages = $value;
            $value = ResourceValue::CAR_COVERAGE_ALL;
        }

        return array_get($this->coverageToPastonId, (string)$value, '');
    }

    protected function convertFromCoverage($value)
    {
        return array_search((string)$value, $this->coverageToPastonId, true);
    }

    protected function invertBoolean($value)
    {
        return !(bool)$value;
    }

    protected function makeGenericOwnRisk($value)
    {
        return [[
            'type' => 'GENERIC',
            'val' => (int)$value,
        ]];
    }

    protected function convertSecurityClassId($value)
    {
        if (!isset($this->securityClasses[(int)$value])) {
            $this->setErrorString('Unknown security class input: `' . $value . '`');
            $value = 0;
        }

        return $this->securityClasses[(int)$value];
    }

    protected function convertDepreciationScheme($value)
    {
        if (!isset($this->depreciationTypesToPastonDepreciationTypes[$value])) {
            $this->setErrorString('Unknown depreciation input: `' . $value . '`');
            $value = ResourceValue::DEPRECIATION_STANDARD;
        }

        return $this->depreciationTypesToPastonDepreciationTypes[$value];
    }

    protected function createResourceId($value, $item)
    {
        return $item[ResourceInterface::COMPANY_NAME] .' - '. $item[ResourceInterface::NAME];
    }

    protected function getAdditionalCoverage($value, $item, $outputParamName)
    {
        $paramName = str_replace('_value', '', $outputParamName);

        // We only try to match netherlands roadside assistance for now
        if ($outputParamName == ResourceInterface::ROADSIDE_ASSISTANCE_EUROPE_VALUE || $outputParamName == ResourceInterface::ROADSIDE_ASSISTANCE_VALUE)
            return null;
        if ($outputParamName == ResourceInterface::ROADSIDE_ASSISTANCE_NETHERLANDS_VALUE)
            $paramName = ResourceInterface::ROADSIDE_ASSISTANCE;

        foreach ((array)$value as $coverage)
        {
            if (array_search($coverage['type'], $this->additionalCoverageToPastonCoverIds) == $paramName) {
                return $coverage['isAvailable'] ? $coverage['netPremium'] / $this->inputParams[ResourceInterface::PAYMENT_PERIOD] : null;
            }
        }

        return null;
    }

    protected function getAccessoiresAmount($value, $item, $outputParamName)
    {
        foreach ((array)$value as $coverage)
        {
            if (array_search($coverage['type'], $this->additionalCoverageToPastonCoverIds) == ResourceInterface::ACCESSOIRES_COVERAGE) {
                if (isset($coverage['insuredAmounts'][0]['val']))
                    return $coverage['insuredAmounts'][0]['val'];
                else
                    ;// Log error
            }
        }

        return null;
    }

    protected function getAccidentCoverage($value, $item, $outputParamName)
    {
        foreach ((array)$value as $coverage)
        {
            if (array_search($coverage['type'], $this->coverageToPastonInsuredAmountTypeIds) === $outputParamName)
                return $coverage['val'];
        }

        return null;
    }

    protected function getGenericOwnRisk($value, $product)
    {
        foreach ($product['@unmapped']['ownRisks'] as $ownRisk)
            if ($ownRisk['type'] === 'GENERIC')
                return $ownRisk['val'];

        return null;
    }

    protected function convertPriceToMonth($value)
    {
        return $value / $this->inputParams[ResourceInterface::PAYMENT_PERIOD];
    }

    protected function formatPostalCode($value)
    {
        $neighborhoodCode = substr($value, 0, 4);
        $letterCode = substr($value, 4);

        return $neighborhoodCode .' '. strtoupper(trim($letterCode));
    }

    public function setParams(array $params)
    {
        $this->inputParams = $params;

        // TODO: We always overwrite 'no_claim' here - because product.carinsurance also has a 'no_claim' and it messes with this input. Should rename/fix.
        $params[ResourceInterface::NO_CLAIM] = (isset($params[ResourceInterface::YEARS_WITHOUT_DAMAGE]) && $params[ResourceInterface::YEARS_WITHOUT_DAMAGE] == 0);

        if (empty($params[ResourceInterface::LICENSEPLATE]) && !empty($params[ResourceInterface::TYPE_ID]))
        {
            $params = $this->fetchRollsTypeData($params);
        }

        if (!empty($params[ResourceInterface::LICENSEPLATE]) && empty($params[ResourceInterface::BRAND_NAME]))
        {
            if ($this->carDataUsage['rdw'] && !$this->hasErrors())
                $params = $this->fetchCarRdwData($params);              // For: body_type, amount_of_doors, cylinder_volume, weight, color
            if ($this->carDataUsage['rdw_fuel'] && !$this->hasErrors())
                $params = $this->fetchCarRdwFuelData($params);      // For: fuel_type, power
            if ($this->carDataUsage['rolls_basic'] && !$this->hasErrors())
                $params = $this->fetchCarRollsData($params, false);        // For: fuel_type, brand_name, model_name, type_name, catalogus_value, daily_value,
            if ($this->carDataUsage['rolls_premium'] && !$this->hasErrors())
                $params = $this->fetchCarRollsData($params, true);        // For: Everything
            if ($this->carDataUsage['isa'] && !$this->hasErrors())
                $params = $this->fetchCarIsaData($params);        // For: Everything, currently excluding body-type

            $vehicleType = array_get($params, ResourceInterface::VEHICLE_TYPE, false);
            if ($vehicleType && ($vehicleType !== 'PassengerCar' && $vehicleType !== 'Passengercar'))
            {
                $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'paston.no_passengercar', 'Dit voertuig is geen passagiersauto, maar een `'. $vehicleType .'`.');
                $this->invalidVehicleType = true;
            }
        }

        foreach ($this->requiredInput as $paramName) {
            if (!isset($params[$paramName]) || $params[$paramName] === '') {
                $this->invalidVehicleType = true;
                if (!$this->hasErrors()) {
                    $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'paston.car_data_incomplete', 'Kan geen volledige auto-informatie verzamelen van voertuig.');
                }
                $this->addErrorMessage($paramName, 'paston.car_data_required', 'Missende auto informatie: `'. $paramName .'`.');
            }
        }

        parent::setParams($params);

        if ($this->hasErrors())
            $this->notifyPremiumError('Probleem bij invoer gegevens.');
    }

    protected function applyParams(array $httpOptions)
    {
        // Some parameters need to be in the URL query instead of the JSON POST body
        foreach ($this->getQueryParams as $paramName) {
            if (isset($this->params[$paramName]))
                $httpOptions['query'][$paramName] = $this->params[$paramName];
            unset($this->params[$paramName]);
        }

        return parent::applyParams($httpOptions);
    }

    protected function fetchCarRdwData($params)
    {
        $this->rdwCarData = $this->internalRequest('rdw', 'licenseplate', [
            ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
        ]);

        if ($this->rdwCarData == [])
        {
            $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'paston.no_rdw_data', 'Dit voertuig is helaas niet (meer) geregistreerd bij het RDW.');
        }
        else if ($this->resultHasError($this->rdwCarData))
        {
            $this->addErrorMessage(ResourceInterface::LICENSEPLATE, 'paston.rdw_not_available', 'Een fout is opgetreden tijdens auto-informatie aanvraag bij het RDW.');
        }

        if ($this->resultHasError($this->rdwCarData) || $this->rdwCarData == []) {
            return $params;
        }

        if (isset($this->rdwBodyTypeToPastonBodyType[$this->rdwCarData[ResourceInterface::BODY_TYPE]]))
            $this->rdwCarData[ResourceInterface::BODY_TYPE] = $this->rdwBodyTypeToPastonBodyType[$this->rdwCarData[ResourceInterface::BODY_TYPE]];

        if (isset($this->rdwColorToPastonColor[$this->rdwCarData[ResourceInterface::COLOR]]))
            $this->rdwCarData[ResourceInterface::COLOR] = $this->rdwColorToPastonColor[$this->rdwCarData[ResourceInterface::COLOR]];

        return array_merge($params, $this->rdwCarData);
    }

    protected function fetchCarRdwFuelData($params)
    {
        $this->rdwFuelData = $this->internalRequest('rdw', 'licenseplate.fuel', [
            ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
        ], true);

        if (!$this->resultHasError($this->rdwFuelData) && $this->rdwFuelData != [])
        {
            $fuelData = $this->rdwFuelData;

            $fuelData['@unmapped'] = array_merge($params['@unmapped'], $fuelData['@unmapped']);

            foreach ($fuelData['additional_fuels'] as $fuel)
                $fuelData[ResourceInterface::FUEL_TYPE_NAME] .= '-'. $fuel[ResourceInterface::FUEL_TYPE_NAME];

            $params = array_merge($params, $fuelData);

            if (isset($this->rdwFuelToPastonFuel[$params[ResourceInterface::FUEL_TYPE_NAME]]))
                $params[ResourceInterface::FUEL_TYPE_ID] = $this->rdwFuelToPastonFuel[$params[ResourceInterface::FUEL_TYPE_NAME]];
            else
                $params[ResourceInterface::FUEL_TYPE_ID] = null; // TODO: Add error here
        }

        // Paston can't handle decimals
        if (isset($params[ResourceInterface::POWER]))
            $params[ResourceInterface::POWER] = (int)$params[ResourceInterface::POWER];

        return $params;
    }

    protected function fetchCarRollsData($params, $premiumData)
    {
        try {
            $this->rollsBasicData = ResourceHelper::callResource2('licenseplate_premium.carinsurance', [
                ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
            ]);
        }
        catch (ResourceError $e) {
            $this->rollsBasicData = ['error' => json_encode($e->getMessages())];
            if ($this->debug)
                $this->setErrorString('Rolls resource error: '. json_encode($e->getMessages()));
            return $params;
        }
        catch (\Exception $e) {
            $this->rollsBasicData = ['error' => $e->getMessage()];
            if ($this->debug)
                $this->setErrorString('Rolls error: '. $e);
            return $params;
        }

        $rollsBasicData = $this->rollsBasicData;

        // We do not want the default security class, customer provides this
        unset($rollsBasicData[ResourceInterface::SECURITY_CLASS_ID]);

        if (isset($rollsBasicData[ResourceInterface::FUEL_TYPE_ID], $this->rollsBasicFuelToPastonFuel[$rollsBasicData[ResourceInterface::FUEL_TYPE_ID]]))
            $rollsBasicData[ResourceInterface::FUEL_TYPE_ID] = $this->rollsBasicFuelToPastonFuel[$rollsBasicData[ResourceInterface::FUEL_TYPE_ID]];
        else
            unset($rollsBasicData[ResourceInterface::FUEL_TYPE_ID]); // Add error reporting here


        if (empty($rollsBasicData[ResourceInterface::REPLACEMENT_VALUE]))
            unset($rollsBasicData[ResourceInterface::REPLACEMENT_VALUE]);
        if (empty($rollsBasicData[ResourceInterface::DAILY_VALUE]))
            unset($rollsBasicData[ResourceInterface::DAILY_VALUE]);

        // Following data not in Rolls Basic car data
        if (isset($rollsBasicData[ResourceInterface::COLOR], $this->rdwColorToPastonColor[$rollsBasicData[ResourceInterface::COLOR]]))
            $rollsBasicData[ResourceInterface::COLOR] = $this->rdwColorToPastonColor[$rollsBasicData[ResourceInterface::COLOR]];
        if (isset($rollsBasicData[ResourceInterface::DRIVE_TYPE], $this->rollsWheelDriveToPaston[$rollsBasicData[ResourceInterface::DRIVE_TYPE]]))
            $rollsBasicData[ResourceInterface::DRIVE_TYPE] = $this->rollsWheelDriveToPaston[$rollsBasicData[ResourceInterface::DRIVE_TYPE]];
        if (isset($rollsBasicData[ResourceInterface::TRANSMISSION_TYPE], $this->rollsTransmissionToPaston[$rollsBasicData[ResourceInterface::TRANSMISSION_TYPE]]))
            $rollsBasicData[ResourceInterface::TRANSMISSION_TYPE] = $this->rollsTransmissionToPaston[$rollsBasicData[ResourceInterface::TRANSMISSION_TYPE]];
        if (isset($rollsBasicData[ResourceInterface::BODY_TYPE], $this->rollsBodyTypeToPastonBodyType[$rollsBasicData[ResourceInterface::BODY_TYPE]]))
            $rollsBasicData[ResourceInterface::BODY_TYPE] = $this->rollsBodyTypeToPastonBodyType[$rollsBasicData[ResourceInterface::BODY_TYPE]];

        return array_merge($params, $rollsBasicData);
    }

    protected function fetchCarIsaData($params)
    {
        $this->isaCarData = $this->internalRequest('isa', 'licenseplate', [
            ResourceInterface::LICENSEPLATE => $params[ResourceInterface::LICENSEPLATE],
        ], true);

        if ($this->debug && $this->resultHasError($this->isaCarData))
            $this->setErrorString('ISA error: '. json_encode($this->isaCarData));

        if ($this->resultHasError($this->isaCarData))
            return $params;

        $isaCarData = $this->isaCarData;

        if (isset($isaCarData[ResourceInterface::FUEL_TYPE_ID], $this->isaFuelTypeToPaston[$isaCarData[ResourceInterface::FUEL_TYPE_ID]]))
            $isaCarData[ResourceInterface::FUEL_TYPE_ID] = $this->isaFuelTypeToPaston[$isaCarData[ResourceInterface::FUEL_TYPE_ID]];
        else
            unset($isaCarData[ResourceInterface::FUEL_TYPE_ID]); // Add error reporting here

        // Following data not in Rolls Basic car data
        if (isset($isaCarData[ResourceInterface::COLOR], $this->isaColorToPaston[$isaCarData[ResourceInterface::COLOR]]))
            $isaCarData[ResourceInterface::COLOR] = $this->isaColorToPaston[$isaCarData[ResourceInterface::COLOR]];
        else
            $isaCarData[ResourceInterface::COLOR] = null;

        if (isset($isaCarData[ResourceInterface::DRIVE_TYPE], $this->isaWheelDriveToPaston[$isaCarData[ResourceInterface::DRIVE_TYPE]]))
            $isaCarData[ResourceInterface::DRIVE_TYPE] = $this->isaWheelDriveToPaston[$isaCarData[ResourceInterface::DRIVE_TYPE]];
        if (isset($isaCarData[ResourceInterface::TRANSMISSION_TYPE], $this->isaTransmissionToPaston[$isaCarData[ResourceInterface::TRANSMISSION_TYPE]]))
            $isaCarData[ResourceInterface::TRANSMISSION_TYPE] = $this->isaTransmissionToPaston[$isaCarData[ResourceInterface::TRANSMISSION_TYPE]];
        if (isset($isaCarData[ResourceInterface::BODY_TYPE], $this->isaObjectCodeToPastonBodyType[$isaCarData[ResourceInterface::BODY_TYPE]]))
            $isaCarData[ResourceInterface::BODY_TYPE] = $this->isaObjectCodeToPastonBodyType[$isaCarData[ResourceInterface::BODY_TYPE]];
        if (isset($isaCarData[ResourceInterface::BODY_TYPE], $this->isaObjectCodeNonPersonalCars[$isaCarData[ResourceInterface::BODY_TYPE]]))
            $isaCarData[ResourceInterface::VEHICLE_TYPE] = $this->isaObjectCodeNonPersonalCars[$isaCarData[ResourceInterface::BODY_TYPE]];
        if (isset($isaCarData[ResourceInterface::CATEGORY]))
            $isaCarData[ResourceInterface::CATEGORY] = array_get($this->isaSegmentIdToCategoryId, $isaCarData[ResourceInterface::CATEGORY]);

        return array_merge($params, $isaCarData);
    }

    protected function fetchRollsTypeData($params)
    {
        if (isset($params[ResourceInterface::CONSTRUCTION_DATE])) {
            $params[ResourceInterface::CONSTRUCTION_DATE_YEAR] = date('Y', strtotime($params[ResourceInterface::CONSTRUCTION_DATE]));
            $params[ResourceInterface::CONSTRUCTION_DATE_MONTH] = (int)date('m', strtotime($params[ResourceInterface::CONSTRUCTION_DATE]));
        }

        $rollsData = [];
        if (isset($params[ResourceInterface::CONSTRUCTION_DATE_YEAR], $params[ResourceInterface::CONSTRUCTION_DATE_MONTH], $params[ResourceInterface::BRAND_ID], $params[ResourceInterface::MODEL_ID], $params[ResourceInterface::TYPE_ID])) {

            $rollsData = $this->internalRequest('carinsurance', 'cartype', [
                ResourceInterface::CONSTRUCTION_DATE_YEAR => $params[ResourceInterface::CONSTRUCTION_DATE_YEAR],
                ResourceInterface::CONSTRUCTION_DATE_MONTH => $params[ResourceInterface::CONSTRUCTION_DATE_MONTH],
                ResourceInterface::BRAND_ID => $params[ResourceInterface::BRAND_ID],
                ResourceInterface::MODEL_ID => $params[ResourceInterface::MODEL_ID],
                ResourceInterface::TYPE_ID => $params[ResourceInterface::TYPE_ID],
            ], true);

            if ($this->resultHasError($rollsData)) {
                $this->addErrorMessage(null, 'rolls.type_lookup', 'Rolls type lookup error: ' . json_encode($rollsData));
                $rollsData = [];
            }

            if (isset($rollsData[ResourceInterface::COACHWORK_TYPE_ID]))
                $rollsData[ResourceInterface::BODY_TYPE] = array_get($this->rollsBodyTypeIdToPastonBodyType, $rollsData[ResourceInterface::COACHWORK_TYPE_ID]);

            $rollsData[ResourceInterface::CONSTRUCTION_DATE] = date('Y-m-d', strtotime($params[ResourceInterface::CONSTRUCTION_DATE_YEAR] .'-'. $params[ResourceInterface::CONSTRUCTION_DATE_MONTH] .'-01'));
        }

        return array_merge($params, $rollsData);
    }

    public function executeFunction()
    {
        if ($this->invalidVehicleType)
            $this->result = [];
        else
            parent::executeFunction();

        if ($this->hasErrors())
            $this->notifyPremiumError('Probleem tijdens ophalen resultaten.');
    }

    public function getResult()
    {
        $result = array_filter(parent::getResult(), function ($row) {
            // If we have two coverages we want, we request 'all' and filter here.
            return !isset($this->filterCoverages) || in_array($row[ResourceInterface::COVERAGE], $this->filterCoverages);
        });

        if ($this->hasErrors()) {
            $this->notifyPremiumError('Probleem tijdens verwerken resultaten.');
        }
        else if (count($result) === 0) {
            $this->notifyPremiumError('Geen premium resultaten ontvangen.');
        }

        return $result;
    }

    protected function notifyPremiumError($errorMessage)
    {
        if (!((app()->configure('resource_paston')) ? '' : config('resource_paston.settings.premium_error_notification_emails')))
            return;

        if ($this->hasNotified)
            return;

        $this->hasNotified = true;

        $resourceData = [];
        foreach (['params', 'inputParams', 'rdwCarData', 'rdwFuelData', 'rollsBasicData', 'isaCarData', 'errorMessages'] as $data)
            $resourceData[$data] = $this->{$data};

        Mail::send('emails.paston_premium_error', ['resource' => $this, 'resourceData' => (array)$resourceData, 'errorMessage' => $errorMessage], function (Message $message) use ($resourceData, $errorMessage) {
            $message->to(((app()->configure('mail')) ? '' : config('mail.product_mail_to')))->from(((app()->configure('mail')) ? '' : config('mail.product_from')), 'Komparu Reporting');
            $message->subject('Meeùs error report ['. (isset($resourceData['inputParams'][ResourceInterface::LICENSEPLATE]) ? $resourceData['inputParams'][ResourceInterface::LICENSEPLATE] : '')  .']: ' . $errorMessage);

            foreach(((app()->configure('resource_paston')) ? '' : config('resource_paston.settings.premium_error_notification_emails')) as $emailAddress){
                $message->to($emailAddress);
            }
        });
    }
}