<?php

return [

    'default' => 'mysql',

    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST'),
            'database'  => env('DB_DATABASE'),
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

        'mysql_product' => [
            'driver'    => 'mysql',
            'host'      => env('MYSQL_PRODUCT_HOST'),
            'database'  => env('MYSQL_PRODUCT_NAME'),
            'username'  => env('MYSQL_PRODUCT_USER'),
            'password'  => env('MYSQL_PRODUCT_PASS'),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],
    ],
];
