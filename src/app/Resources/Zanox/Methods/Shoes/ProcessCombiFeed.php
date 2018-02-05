<?php
/**
 * Created by PhpStorm.
 * User: jeroen
 * Date: 9/9/15
 * Time: 2:14 PM
 */

namespace App\Resources\Zanox\Methods\Shoes;


use App\Helpers\ResourceFilterHelper;

class ProcessCombiFeed
{
    private $filterMapping = [
        'ean'            => 'split_to_array',
        'size'           => 'split_to_array',
        'size_stock'     => 'split_to_array',
        'price'          => 'comma_to_dot',
        'price_shipping' => 'comma_to_dot',
        'price_old'      => 'comma_to_dot',
        'stock'          => 'check_stock',
        'category_path'  => 'breadcrumbs_to_array',
    ];

    private $data;

    public function setData(Array $data)
    {

        $this->data = $data;
    }

    public function process()
    {

        $returnRow = [];
        $mapping   = $this->filterMapping;

        foreach($this->data as $key => $value){
            if(isset($mapping[$key])){//$this->filterMapping
                $filtername      = $this->filterMapping[$key];
                $returnRow[$key] = ResourceFilterHelper::$filtername($value);
                continue;
            }
            $returnRow[$key] = $value;

            // an empty XML element e.g. <empty /> wil result in a JSON array without values, we're flattening this so we don't loose the key
            if(is_array($value)){ // Or: (count($value) == 0), what is best?
                $returnRow[$key] = '';
            }

        }
        return $returnRow;

    }


}