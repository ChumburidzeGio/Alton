<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:44
 *
 */


return [
    'settings' => [
        'url'      => ((app()->environment() == 'prod') ? 'https://komparu.z-advies.nl/apps/rest/Komparu' : 'https://keuzenl-acc.z-advies.nl/apps/rest/Komparu'),
        'username' => 'roelandwerring',
        'password' => 'Komp@30',
        'year'     => 2018,

    ]
];
