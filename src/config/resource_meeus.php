<?php

return [
    'settings' => [
        'wsdl_url' => 'https://autovergelijker.meeus.com/ws_prod/services/ISAservice.asmx?WSDL',
        'wsdl_file' => 'data/meeus_isa_wsdls/production.wsdl',
        //'wsdl_url' => 'https://autovergelijkera.meeus.com/ws_accp/services/ISAservice.asmx?WSDL',
        //'wsdl_file' => 'data/meeus_isa_wsdls/acceptation.wsdl',
        //'wsdl_environment' => 'acceptation',
        'meeus_ccs_form_url' => 'https://eforms.meeus.com/Meeus/servletcontroller',
        'wsdl_environment' => 'production',
        'account' => 'ISA3',
        'username' => 'SA-Komparu',
        'password' => '!eD&mNu#81W8',
        'company_number' => 3,
        'intermediary_number' => 1,
    ]
];
