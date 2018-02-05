<?php
/**
 * User: Roeland Werring
 * Date: 10/03/15
 * Time: 15:38
 *
 */

namespace App\Resources\Moneyview\Methods;

use App\Resources\Moneyview\MoneyviewServiceRequest;
use Config;

class AbstractProductListClient extends MoneyviewAbstractSoapRequest
{
    protected $cacheDays = 7;

    protected $moneyviewModuleName = '';

    public $skipDefaultFields = true;

    public function __construct()
    {
        parent::__construct( '', self::TASK_LOOKUP );
        $this->defaultParams = [
            self::TASK_KEY          => self::TASK_PROCESS_ONE,
            self::GLOBAL_KEY        => self::GLOBAL_ALL,
            self::FIELD_KEY         => 'companies_products_codes',
            self::MODULE_KEY        => $this->moneyviewModuleName,
            self::BEREKENING_MY_KEY => Config::get( 'resource_moneyview.settings.code' ),

        ];
    }

    public function getResult()
    {
        $res       = parent::getResult();
        $returnArr = [ ];
        foreach ($res as $key => $val) {
            if (isset($val['LOCAL']) && isset($val['SPECIFIC'])) {
                $val[MoneyviewServiceRequest::TITLE] = $val['LOCAL'].' '.$val['SPECIFIC'];
            }
            $val[MoneyviewServiceRequest::COMP_NAME] = $val['LOCAL'];
            $val[MoneyviewServiceRequest::SPEC_NAME] = $val['SPECIFIC'];
            unset($val['LOCAL']);
            unset($val['SPECIFIC']);
            $returnArr[] = $val;
        }
        return $returnArr;
    }
}