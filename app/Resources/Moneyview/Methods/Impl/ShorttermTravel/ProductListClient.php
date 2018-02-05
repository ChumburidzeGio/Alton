<?php
/**
 * User: Roeland Werring
 * Date: 10/03/15
 * Time: 15:38
 *
 */

namespace App\Resources\Moneyview\Methods\Impl\ShorttermTravel;

use App\Resources\Moneyview\Methods\AbstractProductListClient;
use Config;

class ProductListClient extends AbstractProductListClient
{
    protected $moneyviewModuleName = 'reiskort';
}
