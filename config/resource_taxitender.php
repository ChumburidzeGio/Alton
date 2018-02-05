<?php
return [
    'settings' => [
        'url'     => ((app()->environment() == 'prod') ?'https://api.taxitender.com/booking/affiliate/v2/':'http://testapi.yattaxi.com/booking/affiliate/v2/'),
        'auth'    => [
            'apiLogin'    =>  ((app()->environment() == 'prod') ?'parcompare':'komparu'),
            'apiToken'    => ((app()->environment() == 'prod')? '59a81721b464f0937cb378535b7890fb0198027d':'e32a98c63c3d515eae1321a3d43c57f9048874b7'),
            'label'       => 'taxiTender',
            'affiliateID' => ((app()->environment() == 'prod')? '776313723':'680021775'),
        ],
        'default' => [
            'languageCode'    => 'NL',
            'currencyIsoCode' => 'EUR',
            'paymentMethod' => 'invoice',
        ],
        'mixClasses' => false,
    ]
];


