<?php
namespace App\Resources\Blaudirekt\Requests;

use App\Interfaces\ResourceInterface;
use App\Resources\Blaudirekt\BlaudirektAbstractRequest;
use DB;

class IndexRequest extends BlaudirektAbstractRequest
{
    protected $resultTransformations = [
        ResourceInterface::COVERAGE => 'coverageTT'
    ];

    public function setParams(array $params)
    {
        parent::setParams($params);
    }

    public function executeFunction()
    {
        $joinStatemnt = DB::connection('mysql_product')->table('product_privateliabilityde_blaudirekt')
            ->selectRaw('company_id, MIN(coverage_sum) a, MIN(business_loss_off_keys) b, MIN(contract_deductible) c, MIN(loss_off_debt_income_insurance) d')
            ->groupBy('company_id');

        $this->filterByParams($joinStatemnt);

        $products = DB::connection('mysql_product')->table('product_privateliabilityde_blaudirekt as pp')
            ->select(DB::raw('DISTINCT pp.*'))
            ->setBindings($joinStatemnt->getRawBindings())
            ->join(DB::raw("({$joinStatemnt->toSql()}) pp2"), function ($q) {

                return $q
                    ->on('pp.coverage_sum', '=', 'pp2.a')
                    ->on('pp.business_loss_off_keys', '=', 'pp2.b')
                    ->on('pp.contract_deductible', '=', 'pp2.c')
                    ->on('pp.loss_off_debt_income_insurance', '=', 'pp2.d')
                    ->on('pp.company_id', '=', 'pp2.company_id');
            });

        $this->filterByParams($products, 'pp.');

        $this->result = array_map(function($x){
            return (array) $x;
        }, $products->get());
    }

    public function filterByParams($query, $prefix = '')
    {
        $params = $this->inputParams;

        foreach (['coverage_sum', 'business_loss_off_keys', 'contract_deductible', 'loss_off_debt_income_insurance'] as $param) {
            $query->where($prefix.$param, '>', array_get($params, $param, 0));
        }
    }

    public function coverageTT($input)
    {
        return json_decode($input);
    }

}