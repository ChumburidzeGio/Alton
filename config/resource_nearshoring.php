<?php
/**
 * User: Roeland Werring
 * Date: 17/03/15
 * Time: 11:44
 *
 */

return [
    'settings' => [
        'service'  => ((app()->environment() == 'prod') ? 'https://services.mijniak.nl/Core.Sales.WcfHost/SalesService.svc' : 'https://iop-wcf.zorginkijk.nl/Core.Sales.Wcfhost/SalesService.svc'),
        'username' => 'IOPSalseServiceUser',
        'password' => '5^%gfFFghf$%Dfvvf',
        'url'      => ((app()->environment() == 'prod') ? 'https://iak.nl/zorgverzekering/premieberekening-zorg?SalesId=' : 'https://acc.iak.nl/zorgverzekering/premieberekening-zorg?SalesId=')
    ]
];

