<?php
return [
    'settings' => [
        'wsdlUrl'       => ((app()->environment() == 'prod') ? 'https://secure.schiphol.nl/bookingws/service201510.asmx?WSDL': 'https://schiphol.chauntrylab2.com/bookingws3/Service201510.asmx?WSDL'),
        'wsdlNamespace' => 'http://www.chauntry.com/bookingws3/2015/10/',
        'username'      => ((app()->environment() == 'prod') ? 'mM7NZnfWLc' : 'JAu73bg7MN'),
        'password'      => ((app()->environment() == 'prod') ? 'GE8LfVwcJ2' : 'dk8nU363LS'),
        'customer_code' => 'schiphol',
        'agent_code'    => 'PAR',
    ]
];

