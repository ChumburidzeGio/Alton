<?php
/**
 * User: Roeland Werring
 * Date: 13/03/15
 * Time: 13:01
 */

namespace App\Resources\Moneyview;

use App\Resources\AbstractServiceRequest;

class MoneyviewServiceRequest extends AbstractServiceRequest
{
    protected $fieldMapping = [
        'LOCAL'                     => self::TITLE,
        'SPECIFIC'                  => self::SPEC_NAME,
        'CODE'                      => self::RESOURCE_ID,
        'INB_MAX_VB'                => self::UNDERINSURANCE,
        'NVT'                       => self::NA,
        'GARANTIE'                  => self::WARRANTY,
        'POLISKOSTEN'               => self::PRICE_INITIAL,
        'PREMIE_TOTAAL'             => self::PRICE_DEFAULT,
        'PREMIE'                    => self::PRICE_DEFAULT,
        'TERMIJN_PREMIE'            => self::PRICE_DEFAULT,
        'DEKKINGSGEBIED'            => self::COVERAGE_AREA,
        'EIGENRISICO'               => self::OWN_RISK,
        'EIGENRISICO_ALGEMEEN'      => self::OWN_RISK,
        'DEKKING_BAGAGE'            => self::COVERAGE_LUGGAGE,
        'DEKKING_ONDERWATERSPORT'   => self::COVERAGE_SCUBA_DIVING,
        'DEKKING_GELDCHEQUES'       => self::COVERAGE_CASH_CHEQUES,
        'DEKKING_ANNULERING'        => self::COVERAGE_CANCELLATION,
        'DEKKING_PERIODE'           => self::COVERAGE_PERIOD,
        'VERZEKERDBEDRAG'           => self::COVERAGE_AMOUNT,
        'VERZ_BEDRAG'               => self::COVERAGE_AMOUNT,
        'DEKKING_SCHADEVERHAAL'     => self::COVERAGE_DAMAGE_REDRESS,
        'DEKKING_CONSUMENT'         => self::COVERAGE_CONSUMER,
        'DEKKING_INKOMEN'           => self::COVERAGE_INCOME,
        'DEKKING_VERKEER'           => self::COVERAGE_TRAFFIC,
        'DEKKING_WONEN'             => self::COVERAGE_HOUSING,
        'DEKKING_PLEZIERVAARTUIGEN' => self::COVERAGE_YACHT,
        'DEKKING_FISCAALRECHT'      => self::COVERAGE_TAX_LAW,
        'DEKKING_VERHUUR'           => self::COVERAGE_LEASE,
        'ONBEPERKT'                 => self::UNLIMITED,
        'PD_CONSUMENT'              => self::INSURE_CONSUMER,
        'PD_INKOMEN'                => self::INSURE_INCOME,
        'PD_VERKEER'                => self::INSURE_TRAFFIC,
        'PD_FISCAALRECHT'           => self::INSURE_TAX_LAW,
        'PD_SCHADEVERHAAL'          => self::INSURE_DAMAGE_REDRESS,
        'PD_WONEN'                  => self::INSURE_HOUSING,
        'PD_PLEZIERVAARTUIGEN'      => self::INSURE_YACHT,
        'OPM_CONSUMENT'              => self::REMARK_CONSUMER,
        'OPM_INKOMEN'                => self::REMARK_INCOME,
        'OPM_VERKEER'                => self::REMARK_TRAFFIC,
        'OPM_FISCAALRECHT'           => self::REMARK_TAX_LAW,
        'OPM_SCHADEVERHAAL'          => self::REMARK_DAMAGE_REDRESS,
        'OPM_WONEN'                  => self::REMARK_HOUSING,
        'OPM_PLEZIERVAARTUIGEN'      => self::REMARK_YACHT,
        'SUB_PD'                    => self::PRICE_COVERAGE_SUB_TOTAL,
        'SUB_TOESLAGEN'             => self::PRICE_DISCOUNT,
        'SUB_TOTAAL'                => self::PRICE_SUB_TOTAL,
        'ASSU_BEL'                  => self::PRICE_INSURANCE_TAX,

        // LegalExpensesInsurance
        'KLANT_PREMIE'                  => self::PRICE_DEFAULT,
        'BIJKOMENDE_KOSTEN'             => self::PRICE_SURCHARGES,
        'FRANCHISE'                     => self::FRANCHISE,

        'DEKKING_SCHEIDINGSMEDIATION'     => self::COVERAGE_DIVORCE_MEDIATION,
        'DEKKING_FISCAAL_EN_VERMOGEN'     => self::COVERAGE_TAXES_AND_CAPITAL,
        'DEKKING_PERSONEN_EN_FAMILIERECHT' => self::COVERAGE_FAMILY_LAW,
        'DEKKING_ARBEID'                => self::COVERAGE_WORK,
        'DEKKING_MEDISCH'               => self::COVERAGE_MEDICAL,
        'DEKKING_BURENRECHT'            => self::COVERAGE_NEIGHBOUR_DISPUTES,
        'DEKKING_EIGENWONING'           => self::COVERAGE_HOUSING_OWNED_HOUSE,
        'DEKKING_VERH_EIGENWONING'      => self::COVERAGE_HOUSING_FOR_RENT,
        'DEKKING_VERH_WOONEENH'         => self::COVERAGE_HOUSING_RENTED_LIVINGUNITS,
        'DEKKING_VERH_BEDREENH'         => self::COVERAGE_HOUSING_RENTED_WORKUNITS,
        'DEKKING_MOTORRIJTUIG_ONGEVAL'  => self::COVERAGE_TRAFFIC_ROADVEHICLE_ACCIDENT,
        'DEKKING_MOTORRIJTUIG_OVERIG'   => self::COVERAGE_TRAFFIC_ROADVEHICLE_OTHER,
        'DEKKING_PLEZIERVAARTUIG_ONGEVAL' => self::COVERAGE_TRAFFIC_WATERVEHICLE_ACCIDENT,
        'DEKKING_PLEZIERVAARTUIG_OVERIG' => self::COVERAGE_TRAFFIC_WATERVEHICLE_OTHER,
        'DEKKING_VAKWONING_NL'          => self::COVERAGE_HOUSING_VACATIONHOME_NL,
        'DEKKING_VAKWONING_BUITENL'     => self::COVERAGE_HOUSING_VACATIONHOME_OTHER,

        'OPM_SCHEIDINGSMEDIATION'   => self::REMARK_DIVORCE_MEDIATION,
        'OPM_PERSONEN_EN_FAMILIERECHT' => self::REMARK_FAMILY_LAW,
        'OPM_FISCAAL_EN_VERMOGEN' => self::REMARK_TAXES_AND_CAPITAL,
        'OPM_ARBEID'                => self::REMARK_WORK,
        'OPM_MEDISCH'               => self::REMARK_MEDICAL,
        'OPM_BURENRECHT'            => self::REMARK_NEIGHBOUR_DISPUTES,
        'OPM_EIGENWONING'           => self::REMARK_HOUSING_OWNED_HOUSE,
        'OPM_VERH_EIGENWONING'      => self::REMARK_HOUSING_FOR_RENT,
        'OPM_VERH_WOONEENH'         => self::REMARK_HOUSING_RENTED_LIVINGUNITS,
        'OPM_VERH_BEDREENH'         => self::REMARK_HOUSING_RENTED_WORKUNITS,
        'OPM_MOTORRIJTUIG_ONGEVAL'  => self::REMARK_TRAFFIC_ROADVEHICLE_ACCIDENT,
        'OPM_MOTORRIJTUIG_OVERIG'   => self::REMARK_TRAFFIC_ROADVEHICLE_OTHER,
        'OPM_PLEZIERVAARTUIG_ONGEVAL' => self::REMARK_TRAFFIC_WATERVEHICLE_ACCIDENT,
        'OPM_PLEZIERVAARTUIG_OVERIG' => self::REMARK_TRAFFIC_WATERVEHICLE_OTHER,
        'OPM_VAKWONING'          => self::REMARK_HOUSING_VACATIONHOME,
        'OPM_VAKWONING_NL'          => self::REMARK_HOUSING_VACATIONHOME_NL,
        'OPM_VAKWONING_BUITENL'     => self::REMARK_HOUSING_VACATIONHOME_OTHER,

        'OPM_BIJKOMENDE_KOSTEN'     => self::REMARK_PRICE_SURCHARGES,

        // These will overload 'KLANT_PREMIE' and 'POLISKOSTEN' for PremiumExtendedClient
        'KLANT_PREMIE_INCL'         => self::PRICE_DEFAULT,
        'POLISKOSTEN_INCL'          => self::PRICE_INITIAL,
        'ASSU_PERC'                 => self::TAX_TARIFF,

        //        'algemeen'                   => self::OWN_RISK_GENERAL,
        //        'kinderen'                  => self::OWN_RISK_CHILDREN,

        // Car premium input
        'KEUZELIJST' => self::MODEL_NAME_DESCRIPTION, // A more descriptive form of 'MODEL_NAME'
        'UITVOERINGPRIJSLIJST' => self::TYPE_NAME,
        'MERK' => self::BRAND_NAME, // Name in capitals
        'MODEL' => self::MODEL_NAME,
        'DATUMEERSTENIEUWVRK' => self::START_DATE, // Date of first 'as-new' catagolus sale in YYYYMMDD
        'DATUMLAATSTENIEUWVERK' => self::END_DATE, // Date of last 'as-new' catagolus sale in YYYYMMDD
        'PRIJSGELDIGHEIDSDATUM' => self::PRICE_EXPIRATION_DATE, // In  YYYYMMDD
        'BRANDSTOF' => self::FUEL_TYPE_ID, // 'D' for Diesel, 'B' for all others
        'LEDIGGEWICHT' => self::WEIGHT, // Weight in KG
        'VERMOGENKW' => self::POWER, // Power in Kilowattage
        'ACCELERATIE' => self::ACCELERATION, // Seconds from 0-100 Km/h
        'PRIJSCONSGLD' => self::CATALOGUS_VALUE,
        'BPMBEDRAGGLD' => self::BUSINESS_TAX, // See: https://www.independer.nl/autoverzekering/faq/overige-informatie/wat-is-bpm.aspx
        'VOERTUIGSOORT' => self::VEHICLE_TYPE_ID, // Some ID of some sort of vehicle type table?
        'DEUREN' => self::AMOUNT_OF_DOORS,
        'LAADVERMOGEN' => self::LOAD_CAPACITY,
        'CILINDERINHOUD' => self::CYLINDER_VOLUME,
        'VERSNAANTALVOORUIT' => self::GEAR_AMOUNT_FORWARD,
        'TURBO' => self::TURBO, // 'Turbo' or '' - basically a boolean
        'CARROSSERIEVORM' => self::BODY_TYPE, // Things like 'Stationwagon', 'Sedan'
        'TREINGEWICHT' => self::TRAIN_WEIGHT, // I assume the weight you have to give when transporting via train?
        // 'PRIJSZAKGLD' => self::X, // Not documented - some sort of price in between catalogus value and daily value
        'TYPID' => self::TYPE_ID, // Not documented - some sort of code / id? Number in the range 10000-99999 (Same value as NATCODE)
        'TRANSMISSIE' => self::TRANSMISSION_TYPE,
        'AANDRIJVING' => self::DRIVE_TYPE,
        //'NATCODE' => self::X, // Not documented - some sort of code / id? Number in the range 10000-99999 (Same value as TYPID)

        // Car premium output
        'VORM' => self::COVERAGE,
    ];

    protected $filterMapping = [
        //        self::COVERAGE_CANCELLATION   => 'filterUpperCaseFirst',
        //        self::COVERAGE_CONSUMER       => 'filterUpperCaseFirst',
        //        self::COVERAGE_INCOME         => 'filterUpperCaseFirst',
        //        self::COVERAGE_HOUSING        => 'filterUpperCaseFirst',
        //        self::COVERAGE_TRAFFIC        => 'filterUpperCaseFirst',
        //        self::COVERAGE_YACHT          => 'filterUpperCaseFirst',
        //        self::COVERAGE_TAX_LAW        => 'filterUpperCaseFirst',
        //        self::COVERAGE_LEASE          => 'filterUpperCaseFirst',
        self::COVERAGE_AREA            => 'filterUpperCaseFirst',
        self::COVERAGE_PERIOD          => 'filterNumber',
        self::INSURE_CONSUMER          => 'filterYearToMonth',
        self::INSURE_INCOME            => 'filterYearToMonth',
        self::INSURE_TRAFFIC           => 'filterYearToMonth',
        self::INSURE_TAX_LAW           => 'filterYearToMonth',
        self::INSURE_DAMAGE_REDRESS    => 'filterYearToMonth',
        self::INSURE_HOUSING           => 'filterYearToMonth',
        self::INSURE_YACHT             => 'filterYearToMonth',
        self::PRICE_COVERAGE_SUB_TOTAL => 'filterYearToMonth',
        self::PRICE_DISCOUNT         => 'filterYearToMonth',
        self::PRICE_SUB_TOTAL          => 'filterYearToMonth',
        self::PRICE_INSURANCE_TAX      => 'filterYearToMonth',
        self::PRICE_DEFAULT            => 'filterYearToMonth',
    ];
    protected $serviceProvider = 'moneyview';



    /**
     * Defines if we should ignore the default fields as listed in AbstractServiceRequest
     * @var bool
     */
    public $skipDefaultFields = false;
}
