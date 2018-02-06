<?php

return [
    'driver'          => 'smtp',
    'host'            => 'smtp.sendgrid.net',
    'port'            => 2525,
    'from'            => array('address' => 'reporting@komparu.com', 'name' => 'Komparu Reporting'),
    'encryption'      => 'tls',
    'username'        => 'komparu',
    'password'        => 'superbatrules1',
    'error_mail_to'   => 'roeland.werring@komparu.com',
    'error_mail_cc'   => 'chris.bakker@komparu.com',
    'cron_from'       => 'cronjobs@komparu.com',
    'cron_mail_to'    => 'roeland.werring@komparu.com',
    'cron_mail_cc'    => 'chris.bakker@komparu.com',
    'product_from'    => 'products@komparu.com',
    'product_mail_to' => 'roeland.werring@komparu.com',
    'product_mail_cc' => ['bart.vanraak@komparu.com', 'lars.schreuder@komparu.com', 'kest.bodnya@gmail.com'],
    'blocklist'       => ((app()->environment() == 'prod') ? [] : ['komparu-verzekeringen.nl', 'lancyrverzekeringen.nl', 'parkshuttle24.de', 'parcompare.com', 'iak.nl', 'info@autohotel.nl', 'verzekering.nl']),
    'block_fallback'  => 'komparutest@gmail.com',
    'test_email'      => 'komparutest@gmail.com'
];
