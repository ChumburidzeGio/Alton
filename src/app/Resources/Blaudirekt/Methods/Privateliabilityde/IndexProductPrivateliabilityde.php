<?php
namespace App\Resources\Blaudirekt\Methods\Privateliabilityde;

use App\Interfaces\ResourceInterface;
use App\Resources\Blaudirekt\BlaudirektAbstractRequest;
use App\Resources\Blaudirekt\Requests\PremiumRequest;
use App\Resources\Blaudirekt\Requests\ProductRequest;
use DB;

class IndexProductPrivateliabilityde extends BlaudirektAbstractRequest
{

    protected $insuranceName = 'privathaftpflicht';


    protected $inputToExternalMapping = [
        ResourceInterface::__ID => '__id'
    ];

    protected $resultTransformations = [
        ResourceInterface::COVERAGE => 'coverageTT'
    ];

    protected  $result;

    public function setParams(array $params)
    {
        parent::setParams($params);
    }


    public function getResult()
    {
        return $this->result;
    }

    public function executeFunction()
    {
        $productsQuery = DB::connection('mysql_product')->table('product_privateliabilityde_blaudirekt');

        //This will only work with integers, and do equality check.
        //Do not put another type of value in inputToExternalMapping
        foreach ( $this->params as $paramName => $paramValue)
        {
            $productsQuery = $productsQuery->where($paramName, intval($paramValue));
        }

        $products = $productsQuery->get();

        $grouped = $this->groupBy($products, 'company_id');



        $this->result = array_map(function($items)
        {
            array_multisort(
                array_column($items, 'coverage_sum'),  SORT_ASC,
                array_column($items, 'business_loss_off_keys'), SORT_ASC,
                array_column($items, 'contract_deductible'), SORT_ASC,
                array_column($items, 'loss_off_debt_income_insurance'), SORT_ASC,
                $items
            );

            return head($items);

        }, $grouped);


        return $this->result;
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


    /**
     * From IndexRequest::filterByParams
     * @param $query
     * @param string $prefix
     */
    public function filterByParams($query, $prefix = '')
    {
        $params = $this->inputParams;

        foreach (['coverage_sum', 'business_loss_off_keys', 'contract_deductible', 'loss_off_debt_income_insurance'] as $param) {
            $query->where($prefix.$param, '>', array_get($params, $param, 0));
        }
    }

}