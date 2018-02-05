<?php

switch (app()->environment()) {
    case 'prod':
        $msp_logo_base_url = '//code.komparu.com/userfiles/';
        break;
    case 'acc':
        $msp_logo_base_url = '//code-acc.komparu.com/userfiles/';
        break;
    case 'test':
        $msp_logo_base_url = '//code.komparu.test/userfiles/';
        break;
    default:
        $msp_logo_base_url = '//code.komparu.dev/userfiles/';
}



return [
    'settings' => [
        'url' => 'https://api.multisafepay.com/v1/json/',
        'test_url' => 'https://testapi.multisafepay.com/v1/json/',
        'api_key' => '',
        'language' => 'nl',
        'logo_base_url' => $msp_logo_base_url,
        'test_environment' => false,
    ],
];

