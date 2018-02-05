<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

return array(
    'domain' => ((app()->environment() == 'acc') ? 'http://code-acc.komparu.com' : $_ENV['STATIC_DOMAIN'])
);