<?php
/**
 * User: Roeland Werring
 * Date: 10/03/15
 * Time: 15:44
 *
 */


use App\Interfaces\ResourceInterface;

return [
    'test'               => '0',
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
    'carinsuranceFilter' => [
        'Ditzo',
        'Verzekeruzelf.nl',
        'Reaal',
        'ASR Verzekeringen',
        'Allianz Nederland',
        'Autoweek',
    ],
];
