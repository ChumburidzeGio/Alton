<?php
/**
 * User: Roeland Werring
 * Date: 09/12/14
 * Time: 17:29
 *
 */

use App\Interfaces\ResourceInterface;

return [
    'test'      => ( ! (app()->environment() == 'prod')),
    'settings'  => [
        'list_client'                => '2',
        'soap_xml_trace'             => '0',
        'soap_caching_enabled'       => '1',
        //webservice urls
        'rolls_webservice_url'       => 'http://rollsberekening.kooijmans.nl/rollswebservice/functies_3_0.asmx',
        'rolls_webservicewsdl_url'   => 'http://rollsberekening.kooijmans.nl/rollswebservice/functies_3_0.asmx?wsdl',
        'rolls_lijstservice_url'     => 'http://rollsberekening.kooijmans.nl/rollswebservice/lijsten_1_0.asmx',
        'rolls_lijstservicewsdl_url' => 'http://rollsberekening.kooijmans.nl/rollswebservice/lijsten_1_0.asmx?wsdl',
        'rolls_voertuigservice_url'  => 'http://rollsberekening.kooijmans.nl/rollswebservice/voertuig_1_0.asmx',
        'rolls_voertuigwsdl_url'     => 'http://rollsberekening.kooijmans.nl/rollswebservice/voertuig_1_0.asmx?wsdl',
        //authorisations
        'rolls_clientappkey'         => 'RICZWOL',
        'rolls_clientpassword'       => '123Komparu',
        'rolls_kantoorid'            => '17610',
        //vehicle API info
        'vehicle_api_url'            => 'https://vehicles.rolls.nl/api/',
        'vehicle_api_clientid'       => 'ab2d8a62-0f77-4192-bac1-9a114cc95d6d',
        'vehicle_api_secret'         => 'bcf30617-e450-4748-80b7-f7c4bfc14569',
    ],
    'functions' => [
        //function names
        'function_templ'                         => 'GetFunctieXMLTemplate',
        //auto
        'kenteken_auto_function'                 => 'KS303001',
        //new rolls
        'premie_auto_function'                   => 'KS305701',
        'aanvullendepremie_auto_function'        => 'KS307901',
        //old rolls
        'premie_auto_function_legacy'            => 'KS300701',
        'aanvullendepremie_auto_function_legacy' => 'KS302901',

        'modellen_auto_function'                => 'KS300201',
        'typen_auto_function'                   => 'KS300301',
        'producten_auto_function'               => 'KS300501',
        'merken_auto_function'                  => 'KS300101',
        'polis_auto_function'                   => 'KS300901',
        //bestelauto
        'kenteken_bestelauto_function'          => 'KS303004',
        'premie_bestelauto_function'            => 'KS305704',
        'aanvullendepremie_bestelauto_function' => 'KS307904',
        'merken_bestelauto_function'            => 'KS300104',
        'modellen_bestelauto_function'          => 'KS300204',
        'typen_bestelauto_function'             => 'KS300304',
        'producten_bestelauto_function'         => 'KS300504',
        'premie_bestelauto_function_legacy'     => 'KS300704',
        'polis_bestelauto_function'             => 'KS300904',
        //motor
        'kenteken_motor_function'               => 'KS303002',
        'premie_motor_function'                 => 'KS300702',
        'producten_motor_function'              => 'KS300502',
        'modellen_motor_function'               => 'KS300202',
        'typen_motor_function'                  => 'KS300302',
        'merken_motor_function'                 => 'KS300102',
        'polis_motor_function'                  => 'KS300902',
        //inboedel
        'producten_inboedel_function'           => 'KS300510',
        'premie_inboedel_function'              => 'KS302610',
        'polis_inboedel_function'               => 'KS300910',
        //opstal
        'producten_opstal_function'             => 'KS300511',
        'premie_opstal_function'                => 'KS302611',
        //aansprakelijkheid
        'producten_avp_function'                => 'KS300512',
        'premie_avp_function'                   => 'KS302612',
    ],
    'lists'     => [
        'car_option_list'        => 'KS300001',
        'car_extra_list'         => 'KS300801',
        'motorcycle_option_list' => 'KS300002',
        'van_option_list'        => 'KS300004',
        'home_option_list'       => 'KS300011',
        'contents_option_list'   => 'KS300010',
        'liability_option_list'  => 'KS300012',
    ],
    'options'   => [
        'optie_ja'              => 'ja',
        'optie_nee'             => 'nee',
        //geslacht
        'geslacht_man'          => 'man',
        'geslacht_vrouw'        => 'vrouw',
        //betalingstermijn
        'termijn_maand'         => 'maand',
        'termijn_kwartaal'      => 'kwartaal',
        'termijn_halfjaar'      => 'halfjaar',
        'termijn_jaar'          => 'jaar',
        //actueletermijnpremie" of "toteindevanhetjaar"
        'rekentermijn_toteinde' => 'toteindevanhetjaar',
        'rekentermijn_actuele'  => 'actueletermijnpremie',
        //dekking
        //Wettelijke Aansprakelijkheid
        'dekking_wa'            => 'wa',
        //Beperkt Casco
        'dekking_bc'            => 'bc',
        //Volledig Casco
        'dekking_vc'            => 'vc',
    ],
    //mapping between user and external id. Use this to link to other Kooimans offices

    'static' => [
        'carinsurance' => [
            //Lancyr
            'lancyr' => [
                'wa' => [
                    ResourceInterface::DAMAGE_TO_OTHERS           => true,
                    ResourceInterface::THEFT                      => false,
                    ResourceInterface::FIRE_AND_STORM             => false,
                    ResourceInterface::WINDOW_DAMAGE              => false,
                    ResourceInterface::VANDALISM                  => false,
                    ResourceInterface::OWN_FAULT                  => false,
                    //defaults
                    ResourceInterface::ACCESSOIRES_COVERAGE       => [
                        'geen'    => ['__id' => 1, 'name' => 'geen', 'label' => 'Geen', 'price' => 0],
                        'pakket1' => ['__id' => 2, 'name' => 'pakket1', 'label' => '€ 500 / € 1.250', 'price' => (0 * 1.21)],
                        'pakket2' => ['__id' => 3, 'name' => 'pakket2', 'label' => '€ 1.500 / € 3.500', 'price' => (3.33 * 1.21)],
                        'pakket3' => ['__id' => 4, 'name' => 'pakket3', 'label' => '€ 2.000 / € 5.000', 'price' => (5.00 * 1.21)],
                    ],
                    ResourceInterface::LEGALEXPENSES              => 4.60,
                    ResourceInterface::NO_CLAIM                   => 12.10,
                    ResourceInterface::PASSENGER_INSURANCE_DAMAGE => 4.54,
                    ResourceInterface::PRICE_INITIAL              => 15.13,
                    ResourceInterface::PRICE_FEE                  => 3.03,
                ],
                'bc' => [
                    ResourceInterface::DAMAGE_TO_OTHERS     => true,
                    ResourceInterface::THEFT                => true,
                    ResourceInterface::FIRE_AND_STORM       => true,
                    ResourceInterface::WINDOW_DAMAGE        => true,
                    ResourceInterface::VANDALISM            => false,
                    ResourceInterface::OWN_FAULT            => false,
                    //defaults
                    ResourceInterface::ACCESSOIRES_COVERAGE => [
                        'geen'    => ['__id' => 1, 'name' => 'geen', 'label' => 'Geen', 'price' => 0],
                        'pakket1' => ['__id' => 2, 'name' => 'pakket1', 'label' => '€ 500 / € 1.250', 'price' => (0 * 1.21)],
                        'pakket2' => ['__id' => 3, 'name' => 'pakket2', 'label' => '€ 1.500 / € 3.500', 'price' => (3.33 * 1.21)],
                        'pakket3' => ['__id' => 4, 'name' => 'pakket3', 'label' => '€ 2.000 / € 5.000', 'price' => (5.00 * 1.21)],
                    ],


                    ResourceInterface::LEGALEXPENSES              => 4.60,
                    ResourceInterface::NO_CLAIM                   => 12.10,
                    ResourceInterface::PASSENGER_INSURANCE_DAMAGE => 4.54,
                    ResourceInterface::PRICE_INITIAL              => 15.13,
                    ResourceInterface::PRICE_FEE                  => 3.03,

                ],
                'vc' => [
                    ResourceInterface::DAMAGE_TO_OTHERS     => true,
                    ResourceInterface::THEFT                => true,
                    ResourceInterface::FIRE_AND_STORM       => true,
                    ResourceInterface::WINDOW_DAMAGE        => true,
                    ResourceInterface::VANDALISM            => true,
                    ResourceInterface::OWN_FAULT            => true,
                    //defaults
                    ResourceInterface::ACCESSOIRES_COVERAGE => [
                        'geen'    => ['__id' => 1, 'name' => 'geen', 'label' => 'Geen', 'price' => 0],
                        'pakket1' => ['__id' => 2, 'name' => 'pakket1', 'label' => '€ 500 / € 1.250', 'price' => (0 * 1.21)],
                        'pakket2' => ['__id' => 3, 'name' => 'pakket2', 'label' => '€ 1.500 / € 3.500', 'price' => (3.33 * 1.21)],
                        'pakket3' => ['__id' => 4, 'name' => 'pakket3', 'label' => '€ 2.000 / € 5.000', 'price' => (5.00 * 1.21)],
                    ],

                    ResourceInterface::LEGALEXPENSES              => 4.60,
                    ResourceInterface::NO_CLAIM                   => 12.10,
                    ResourceInterface::PASSENGER_INSURANCE_DAMAGE => 4.54,
                    ResourceInterface::PRICE_INITIAL              => 15.13,
                    ResourceInterface::PRICE_FEE                  => 3.03,
                ]
            ]
        ]
    ]
];



