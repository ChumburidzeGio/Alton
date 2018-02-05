<?php
/**
 * Created by PhpStorm.
 * User: giorgi
 * Date: 11/22/17
 * Time: 10:55 AM
 */

use App\Interfaces\ResourceInterface;

return [
    'debug'           => [
        'exceptions' => false, //Show exceptions
        'rawOutput' => false, //Show raw output in debug (dd)
        'checkForError'  => false, //Checking for errors
    ],
    'settings'           => [
        'wsdl' => 'https://mv1engine.moneyview.nl/services/KomparuEngine/Soap.asmx?WSDL',
        'uid'  => 'KMPR-CABB-2015-EABF-0311',
        'code' => 'KMPR'
    ],
    'choicelist'         => [

        //inboedel
        ResourceInterface::SECURITY                   => 'Whs_Beveiliging',
        ResourceInterface::OUTSIDE                    => 'Whs_Buitenzijde',
        'valuation'                                   => 'inb_waardebepaling',
        ResourceInterface::TYPE                       => 'Whs_Type',
        ResourceInterface::PERSONAL_CIRCUMSTANCES     => 'persoonlijke_omstandigheden',
        ResourceInterface::TYPE_OF_CONSTRUCTION       => 'Whs_Bouwaard',
        ResourceInterface::OWN_RISK                   => 'Berekening_Er',
        'calculation_form'                            => 'Berekening_Vorm',

        //opstal
        ResourceInterface::CONSTRUCTION               => 'Whs_Constructie',
        ResourceInterface::FOUNDATION                 => 'Whs_Fundering',
        ResourceInterface::FINISH                     => 'Whs_Afwerking',

        //travel
        ResourceInterface::COVERAGE_AREA              => 'reis_dekkingsgebied',
        ResourceInterface::COVERAGE_PERIOD            => 'Dekking_Periode',
        ResourceInterface::TOTAL_LUGGAGE              => 'Reis_Totaal_Bagage',
        ResourceInterface::TOTAL_SCUBA_DIVING         => 'Reis_Totaal_Onderwatersport',
        ResourceInterface::TOTAL_CASH_CHEQUES         => 'Reis_Totaal_Geld_Cheques',
        ResourceInterface::COVERAGE_CANCELLATION      => 'Bedrag_Annulering',

        //legal expenses
        ResourceInterface::PERSON_SINGLE              => 'Persoon_Alleenstaand',
        ResourceInterface::CALCULATION_INSURED_AMOUNT => 'Berekening_Verzekerd_Bedrag',
        ResourceInterface::TRAFFIC_COVERAGE           => 'Verkeer_Dekking',

        //liability
        ResourceInterface::OWN_RISK_TYPE              => 'er_soort',
        ResourceInterface::OWN_RISK_CHILDREN          => 'Verzekering_Er',
        ResourceInterface::OWN_RISK_GENERAL           => 'verzekering_er_overig',

        //carinsuruance
        ResourceInterface::FUEL_TYPE_NAME             => 'Autogegevens_Brandstof',

        ResourceInterface::SECURITY_CLASS             => 'Autogegevens_Beveiliging',
        ResourceInterface::BODY_TYPE                  => 'Autogegevens_Carrosserievorm',
        ResourceInterface::TRANSMISSION_TYPE          => 'Autogegevens_Transmissie',
        ResourceInterface::DRIVE_TYPE                 => 'Autogegevens_Aandrijving',
    ],
    'choicelistGroups'         => [

        'contentinsurance' => [
            ResourceInterface::SECURITY,
            ResourceInterface::OUTSIDE,
            'valuation',
            ResourceInterface::TYPE,
            ResourceInterface::PERSONAL_CIRCUMSTANCES,
            ResourceInterface::TYPE_OF_CONSTRUCTION,
            ResourceInterface::OWN_RISK,
            'calculation_form',
            '__id' => 956100,
            '__product_type' => 51,
        ],

        'liabilityinsurance' => [
            ResourceInterface::OWN_RISK_TYPE,
            ResourceInterface::OWN_RISK_CHILDREN,
            ResourceInterface::OWN_RISK_GENERAL,
            ResourceInterface::PERSONAL_CIRCUMSTANCES,
            '__id' => 956200,
            '__product_type' => 52,
        ],

        'homeinsurance' => [
            ResourceInterface::TYPE,
            ResourceInterface::TYPE_OF_CONSTRUCTION,
            ResourceInterface::CONSTRUCTION,
            ResourceInterface::PERSONAL_CIRCUMSTANCES,
            ResourceInterface::OUTSIDE,
            ResourceInterface::FOUNDATION,
            ResourceInterface::FINISH,
            '__id' => 956300,
            '__product_type' => 54
        ],

        'travelinsurance' => [
            ResourceInterface::COVERAGE_AREA,
            ResourceInterface::COVERAGE_PERIOD,
            ResourceInterface::TOTAL_LUGGAGE,
            ResourceInterface::TOTAL_SCUBA_DIVING,
            ResourceInterface::TOTAL_CASH_CHEQUES,
            ResourceInterface::COVERAGE_CANCELLATION,
            '__id' => 956400,
            '__product_type' => 53
        ],

        'legalexpensesinsurance' => [
            ResourceInterface::PERSONAL_CIRCUMSTANCES,
            ResourceInterface::CALCULATION_INSURED_AMOUNT,
            ResourceInterface::TRAFFIC_COVERAGE,
            ResourceInterface::PERSON_SINGLE,
            '__id' => 956500,
            '__product_type' => 55,
        ],

        /*
        'carinsurance' => [
            ResourceInterface::FUEL_TYPE_NAME,
            ResourceInterface::SECURITY_CLASS,
            ResourceInterface::BODY_TYPE,
            ResourceInterface::TRANSMISSION_TYPE,
            ResourceInterface::DRIVE_TYPE,
        ],*/

    ],

    'carinsuranceFilter' => [
        'Ditzo',
        'Verzekeruzelf.nl',
        'Reaal',
        'ASR Verzekeringen',
        'Allianz Nederland',
        'Autoweek',
    ],
];