<?php
/**
 * Created by PhpStorm.
 * User: giorgi
 * Date: 11/22/17
 * Time: 2:58 PM
 */

namespace App\Resources\Moneyview2\Requests;

use App\Interfaces\ResourceInterface;

class ProductListRequest extends BaseRequest
{
    protected $externalToResultMapping = [
        'CODE' => ResourceInterface::RESOURCE_ID,
        'LOCAL' => ResourceInterface::COMPANY_NAME,
        'SPECIFIC' => ResourceInterface::PRODUCT_SUMMARY,
    ];

    protected $resultTransformations = [
        ResourceInterface::TITLE => 'generateTitle',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->clientParams['task'] = 'LOOKUP';

        $this->clientParams['profile'] = [
            'task'  => 'PROCESS_ONE',
            'global' => 'all',
            'field' => 'companies_products_codes',
            'berekening_my' => $this->config('settings.code' )
        ];
    }
}