<?php
namespace App\Resources\Blaudirekt\Methods\Privateliabilityde;

use App\Interfaces\ResourceInterface;
use App\Resources\Blaudirekt\BlaudirektAbstractRequest;
use DB;

class IndexProductPrivateliabilityde extends BlaudirektAbstractRequest
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
        $products = DB::connection('mysql_product')->table('product_privateliabilityde_blaudirekt')->get();

        $grouped = $this->groupBy($products, 'company_id');

        $this->result = array_map(function($items) {

            array_multisort(array_column($items, 'coverage_sum'),  SORT_ASC,
                array_column($items, 'business_loss_off_keys'), SORT_ASC,
                array_column($items, 'contract_deductible'), SORT_ASC,
                array_column($items, 'loss_off_debt_income_insurance'), SORT_ASC,
                $items);

            return head($items);

        }, $grouped);
        /*

        $joinStatemnt = DB::connection('mysql_product')->table('product_privateliabilityde_blaudirekt')
            ->selectRaw('company_id, __id, MIN(CAST(coverage_sum AS UNSIGNED)) a, MIN(CAST(business_loss_off_keys AS UNSIGNED)) b, MIN(CAST(contract_deductible AS UNSIGNED)) c, MIN(CAST(loss_off_debt_income_insurance AS UNSIGNED)) d')
            ->groupBy('company_id');

        $this->filterByParams($joinStatemnt);

        $products = DB::connection('mysql_product')->table('product_privateliabilityde_blaudirekt as pp')
            ->select(DB::raw('DISTINCT pp.*'))
            ->setBindings($joinStatemnt->getRawBindings())
            ->leftJoin(DB::raw("({$joinStatemnt->toSql()}) pp2"), function ($q) {

                return $q->on('pp.__id', '=', 'pp2.__id');
            });

        $this->filterByParams($products, 'pp.');

        $this->result = array_map(function($x){
            return (array) $x;
        }, $products->get());

        print_r($products->toSql());
        exit;*/
    }

    public function coverageTT($input)
    {
        return json_decode($input);
    }

    public function groupBy($input, $key)
    {
        $group = [];

        $input = array_map(function($x){
            return (array) $x;
        }, $input);

        foreach ($input as $value) {
            $group[$value[$key]][] = $value;
        }

        return $group;
    }
}