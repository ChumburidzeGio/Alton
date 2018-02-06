<?php
/**
 * User: Roeland Werring
 * Date: 19/05/15
 * Time: 13:46
 *
 */

namespace App\Resources\Combiner\Methods;

use App\Interfaces\ResourceInterface;
use App\Models\Product;
use App\Models\ProductType;
use App\Resources\AbstractMethodRequest;
use App;

class AbstractProductsRequest extends AbstractMethodRequest
{
    protected $cacheDays = false;


    protected $sources = [];
    protected $destination;
    protected $schemaContext;
    protected $uniqueKeys;

    protected $companyIdMap = [];
    protected $ignoreFields = [];

    protected $multiFields = [
        ResourceInterface::RESOURCE_ID,
        ResourceInterface::RESOURCE_NAME,
        ResourceInterface::PROVIDER_ID,
        ResourceInterface::URL
    ];

    public function executeFunction()
    {
        $this->mergeUID();
    }

    private function mergeUID()
    {
        $this->destination = $this->getRequestType();
        foreach($this->sources as $source){
            $productType  = ProductType::where('name', $source)->firstOrFail();
            $feedProducts = Product::where(array('product_type_id' => $productType->id))->get();

            foreach($feedProducts as $feedProduct){
                if((($company = $feedProduct->company) !== null)){
                    $this->companyIdMap[$company->id][$source] = $feedProduct->uid;
                }
            }
        }
    }

    public function getResult()
    {
        if( ! $this->destination || empty($this->sources)){
            die('nothing to merge here');
        }

        $this->schemaContext = $this->getSchemaContext($this->destination);
        $this->uniqueKeys    = $this->getUniqueKeys($this->schemaContext);

        $result = [];
        //we asume first source is leading
        foreach($this->sources as $source){
            $products = $this->internalRequest($source, 'products');

            //first product
            if(empty($result)){
                //use first product to check cols
                foreach($products as $product){
                    $result[] = $this->createMergedArray($product, $source);
                    //resource.id ==xml_zanox_tmobile51873132
                    //dd($this->createMergedArray($product, $source));
                }
                continue;
            }
            //second product (merge)
            foreach($products as $product){
                $this->mergeProduct($product, $source, $result);

            }
        }
        return $result;
    }

    private function findCompanyId($source, $uid)
    {
        foreach($this->companyIdMap as $compId => $compMap){
            if(isset($compMap[$source]) && $compMap[$source] == $uid){
                return $compId;
            }
        }
        return null;
    }


    private function mergeProduct($product, $source, &$result)
    {
        $product = $this->createMergedArray($product, $source);


        foreach($result as &$resrow){
            $found = true;

            //check if same company
            if($product[ResourceInterface::COMPANY_ID] != $resrow[ResourceInterface::COMPANY_ID]){
                continue;
            }

            foreach($this->uniqueKeys as $ukey){
                if( ! in_array($ukey, $this->ignoreFields) && $product[$ukey] != $resrow[$ukey]){
                    $found = false;
                    break;
                }
            }
            if($found){
                //dubbbele
                if(strpos($resrow[ResourceInterface::RESOURCE_NAME], $product[ResourceInterface::RESOURCE_NAME]) !== false){
                    continue;
                }
                foreach($product as $prodKey => $prodVal){
                    if( ! isset($resrow[$prodKey])){
                        $resrow[$prodKey] = $prodVal;
                    }
                }
                //copy multi fields, comma separated
                foreach ($this->multiFields as $multField) {
                    $resrow[$multField] .= ',' . $product[$multField];
                }
                return;

            }
        }
        $result[] = $product;
        return;
    }

    private function createMergedArray($productRow, $source)
    {
        $returnArr  = [];
        $schemaKeys = array_keys($this->schemaContext);
        foreach($productRow as $key => $value){
            //TODO this is a hack for things with dot notation, should be start with or something like that
            $keyFirstSeg = explode('.', $key)[0];
            if(in_array($keyFirstSeg, $schemaKeys)){
                $returnArr[$key] = $value;
            }
        }
        $returnArrKeys = array_keys($returnArr);
        foreach($this->uniqueKeys as $ukey){
            if( ! in_array($ukey, $returnArrKeys)){
                $returnArr[$ukey] = isset($this->schemaContext[$ukey]) ? $this->schemaContext[$ukey] : 0;
            }
        }
        $returnArr[ResourceInterface::COMPANY_ID] = $this->findCompanyId($source, $returnArr[ResourceInterface::PROVIDER_ID]);

        return $returnArr;

    }
}