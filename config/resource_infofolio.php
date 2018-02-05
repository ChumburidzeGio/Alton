<?php
return [
    'settings' => [
        'service'              => 'https://webservice-2013q2.infofolio.nl/InfofolioService.svc',
        'username'             => 'Komparu01',
        'password'             => 'vih06022017',
        'soapheader_namespace' => 'http://www.w3.org/2005/08/addressing',
        'infoset'              => 'InfosetNVBkomparu01',
        'methods_base'         => 'http://herbouwwaardescanner.nl/IInfofolioService/'
    ],
    'methods'  => [
        'addressinfo'     => 'CheckAddress',
        'realestateinfo' => 'GetRealEstateObjects'
    ]
];
