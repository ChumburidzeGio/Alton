<?php
/**
 * Created by PhpStorm.
 * User: giorgi
 * Date: 11/22/17
 * Time: 2:58 PM
 */

namespace App\Resources\Moneyview2\Methods\ContentInsurance;

use App\Resources\Moneyview2\Requests\ProductListRequest;

class ProductList extends ProductListRequest
{
    public function __construct()
    {
        parent::__construct();

        $this->clientParams['profile']['module'] = 'Inboedel';
    }
}