<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:44
 *
 */

return [
    'settings' => [
        'url' => 'https://api.parkingpro.nl/v1/',
        'apikey' => 'uraucnofnabBewzabometfoHawipovTaxKecjuAfDa',
        'options' =>[
            //Park id -> option_type_id
            11 => [ // Park and Fly
                6 => ['parking_pro_option_identifier' => '56e32394-a617-476e-8f0f-4abca3a733a7', 'option_label' => 'sleutel behouden'],
            ],
            39 => [ // 709, Eazzypark Valet
                1 => ['parking_pro_option_identifier' => 'bb25fc84-cb45-4f83-b5f1-e088f91d6e95','option_label' => 'wassen',],
                12 => ['parking_pro_option_identifier' => '4cee7414-472d-4019-bc57-5624c132715e','option_label' => 'electrisch laden',],
            ],
            25 => [ // 693, Eazzypark
                1 => ['parking_pro_option_identifier' => 'bb25fc84-cb45-4f83-b5f1-e088f91d6e95','option_label' => 'wassen',],
                12 => ['parking_pro_option_identifier' => '4cee7414-472d-4019-bc57-5624c132715e','option_label' => 'electrisch laden',],
            ],
            281 => [ // 2697, Safe & Save Parking
                4 => ['parking_pro_option_identifier' => '6c2f702c-b1f7-4bee-800f-214ed6139307','option_label' => 'overdekt',],
                6 => ['parking_pro_option_identifier' => 'e0e463d7-6b76-413f-bca5-910479b16d65','option_label' => 'sleutel behouden'],
                12 => ['parking_pro_option_identifier' => 'ca99cee4-f6c7-478e-9974-21fa73e37648','option_label' => 'electrisch laden'],
            ],

            257 => [ // 2313, iParking
                1 => ['parking_pro_option_identifier' => '8c9c0d45-19ff-4cf2-89a4-1ba847b8c8ea','option_label' => 'wassen',],
                4 => ['parking_pro_option_identifier' => 'a75e8c9a-7f75-4ab8-9ff5-a4e12b39fa84','option_label' => 'overdekt'],
            ],
            263 => [ // 2341, Parking Point Overdekt
                1 => ['parking_pro_option_identifier' => '0a51547e-626d-43e0-9776-d2443f034a5b','option_label' => 'wassen',],
                4 => ['parking_pro_option_identifier' => '754d8404-3c56-4867-9bc5-3b8abb0c8569','option_label' => 'overdekt',],
                12 => ['parking_pro_option_identifier' => '08e5e215-ca87-4c22-a4a2-243d48a5329f','option_label' => 'electrisch laden'],
            ],

            261 => [ // 2339, Parking Point
                1 => ['parking_pro_option_identifier' => '0a51547e-626d-43e0-9776-d2443f034a5b','option_label' => 'wassen',],
                4 => ['parking_pro_option_identifier' => '754d8404-3c56-4867-9bc5-3b8abb0c8569','option_label' => 'overdekt',],
                12 => ['parking_pro_option_identifier' => '08e5e215-ca87-4c22-a4a2-243d48a5329f','option_label' => 'electrisch laden'],
            ],
            247 => [ // 2291, Xclusive Parking Valet
                1 => ['parking_pro_option_identifier' => '7416849d-3ef4-4115-81c3-5f22fdff4f8b','option_label' => 'wassen',],
                4 => ['parking_pro_option_identifier' => '428addc5-8ff3-4bc6-8946-5e3731e3f4ae','option_label' => 'overdekt'],
                12 => ['parking_pro_option_identifier' => 'b4651561-3ec0-4b09-adbd-83e43f63ab3d','option_label' => 'electrisch laden'],
            ],
            241 => [ // ins id 2287
                12 => ['parking_pro_option_identifier' => 'bb63af0a-480c-48e9-a63c-1ccee39af723','option_label' => 'electrisch laden'],
                4 => ['parking_pro_option_identifier' => '0db0f2ca-f107-424e-86e7-30af8d3a01a4','option_label' => 'overdekt'],
            ],
            249 => [ // ins id 2295
                12 => ['parking_pro_option_identifier' => '87779efd-1de8-4971-a6dd-45db56ff32c1','option_label' => 'electrisch laden'],
            ],
            251 => [ // ins id 2297
                12 => ['parking_pro_option_identifier' => '87779efd-1de8-4971-a6dd-45db56ff32c1','option_label' => 'electrisch laden'],
            ],
            255 => [ // ins id 2309
                1 => ['parking_pro_option_identifier' => '7416849d-3ef4-4115-81c3-5f22fdff4f8b','option_label' => 'wassen',],
            ],
            235 => [ // ins id 2297
                12 => ['parking_pro_option_identifier' => '2f7a5c00-6898-4f27-8677-da1383ef22eb','option_label' => 'electrisch laden'],
            ],
            239 => [ // ins id 2285
                12 => ['parking_pro_option_identifier' => '2f7a5c00-6898-4f27-8677-da1383ef22eb','option_label' => 'electrisch laden'],
            ],
            243 => [ // ins id 2289
                4 => ['parking_pro_option_identifier' => '0db0f2ca-f107-424e-86e7-30af8d3a01a4','option_label' => 'overdekt'],
            ],

            // No longer: Goodparking (235, 239) - Electrisch laden
            // No longer: Budget Valet (241, 243) - Electrisch laden, wassen
        ],
    ],
];

