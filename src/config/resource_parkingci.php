<?php


return [
    'settings' => [
        'testing'  => !(app()->environment() == 'prod'),
        'url'               => 'http://webservice.komparu.com/xml/parking/v1/',
        'username'          => 'parking',
        'password'          => '92d7893a13c491b22b5c04a75c331157',
        // Payment notification is separate 'API'
        'notify_url'        => ((app()->environment() == 'prod') ? 'http://media.komparu.com/parking/notify' : 'http://media.komparu.com/parking/notify_test'),
        'notify_access_key' => md5('Geef mij toegang, k0mp4rud3gekst3!~!'),
    ],
];
