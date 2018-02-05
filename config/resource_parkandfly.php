<?php

return [
    'settings' => [
        'url'    => (in_array(app()->environment(), ['prod', 'acc']) ? 'https://api.parkres.com/v1/' : 'http://api.testing.p-deal.nl/v1/'),
        'apikey' => 'e7gTcIZ639YLTngv'
    ]
];

