<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:44
 *
 */

return [
    'settings' => [
        'url' => ((app()->environment() == 'prod') ? 'https://grizzly.knip.ch/api/v1/':'https://crm-test.knip.de/public-api/v1/'),
        'apikey' => 'KomparuHealth1.0'
    ]
];

