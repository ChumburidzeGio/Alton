<?php

namespace App\Resources\Healthcare\Methods;


use App\Helpers\Healthcare2018Helper;
use App\Helpers\IAKHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use DB;

class CoverageSummary extends HealthcareAbstractRequest
{
    protected $funkyCostTypes = [
        '435390' => '435384'
    ];

    public function executeFunction()
    {
        $this->result = [];
        //Get the coverage name from the cost type id
        $coverage = false;
        if(isset($this->params[ResourceInterface::COST_TYPE_ID])){
            $coverage = DB::connection('mysql_product')->table('cost_types_healthcare2018')->where('__id', $this->params[ResourceInterface::COST_TYPE_ID])->first();
        }

        $conditions = array_only($this->params, [ResourceInterface::WEBSITE, ResourceInterface::USER]);

        if($coverage){

            $providers = ResourceHelper::callResource2('company.healthcare2018', [
                ResourceInterface::ACTIVE  => 1,
                ResourceInterface::WEBSITE => $this->params[ResourceInterface::WEBSITE],
                ResourceInterface::USER    => $this->params[ResourceInterface::USER],
            ]);

            $providerIds = array_map(function ($p) {
                return (int) $p['resource_id'];
            }, $providers);

            $coverageName = head(Healthcare2018Helper::getCoverageName($coverage));

            //Now get the additional products from product.healthcare for this coverage filter
            $productRequestData = [
                ResourceInterface::BIRTHDATE       => isset($this->params[ResourceInterface::BIRTHDATE]) ? $this->params[ResourceInterface::BIRTHDATE] : '1980-01-01',
                ResourceInterface::GROUP_BY        => 'single_product',
                ResourceInterface::TYPE            => '!base',
                $coverageName                      => 1,
                OptionsListener::OPTION_LIMIT      => 99999,
                ResourceInterface::PROVIDER_ID     => $providerIds,
            ];

            if(isset($this->params[ResourceInterface::USER]) && intval($this->params[ResourceInterface::USER]) === IAKHelper::USER_ID){
                $productRequestData[ResourceInterface::COLLECTIVITY_ID] = array_get($this->params, ResourceInterface::COLLECTIVITY_ID, 12281);
            }

            $additionalProducts = ResourceHelper::callResource2('product.healthcare2018', $productRequestData + $conditions);

            //Group them by company and assemble ids to query the product_summaries with
            //Also collect the company logos for returning
            $productsPerCompany = [];
            $productIds         = [];
            $companyImages      = [];
            $companyClickables  = [];
            $companyUrls        = [];
            $companyIds         = [];
            foreach($additionalProducts as $additionalProduct){
                if(stristr($additionalProduct['child_source_ids'], ',')){
                    continue;
                }
                if( ! isset($productsPerCompany[$additionalProduct[ResourceInterface::COMPANY]['name']])){
                    $productsPerCompany[$additionalProduct[ResourceInterface::COMPANY]['name']] = [];
                    $companyImages[$additionalProduct[ResourceInterface::COMPANY]['name']]      = $additionalProduct[ResourceInterface::COMPANY]['image'];
                    $companyClickables[$additionalProduct[ResourceInterface::COMPANY]['name']]  = $additionalProduct[ResourceInterface::COMPANY]['clickable'];
                    $companyUrls[$additionalProduct[ResourceInterface::COMPANY]['name']]        = $additionalProduct[ResourceInterface::COMPANY]['url'];
                    $companyIds[$additionalProduct[ResourceInterface::COMPANY]['name']]         = $additionalProduct[ResourceInterface::COMPANY][ResourceInterface::RESOURCE__ID];
                }
                $productsPerCompany[$additionalProduct[ResourceInterface::COMPANY]['name']][$additionalProduct['__id']] = [
                    'title'                          => $additionalProduct['title'],
                    'product_id'                     => $additionalProduct['__id'],
                    'acceptation'                    => $additionalProduct['acceptation'],
                    ResourceInterface::PRICE_DEFAULT => $additionalProduct[ResourceInterface::PRICE_DEFAULT],
                ];


                //This also serves as a product -> company mapping
                $productIds[$additionalProduct['__id']] = [
                    'company' => $additionalProduct[ResourceInterface::COMPANY]['name']
                ];
            }

            //Use the ids to fetch the product summaries
            $summaries = ResourceHelper::callResource2('product_summaries.healthcare2018', [
                'product_id'   => array_keys($productIds),
                'cost_type_id' => $this->params[ResourceInterface::COST_TYPE_ID],
                '_limit'       => 99999
            ]);

            if($this->isIAKFunkyCostType()){
                $iakProductIds = array_filter($productIds, function ($item) {
                    return $item['company'] === 'IAK';
                });
                //Get the summaries for the cost_type iak uses instead of the normal one
                $iakFunkySummaries = ResourceHelper::callResource2('product_summaries.healthcare2018', [
                    'product_id'   => array_keys($iakProductIds),
                    'cost_type_id' => $this->funkyCostTypes[$this->params[ResourceInterface::COST_TYPE_ID]],
                    '_limit'       => 99999
                ] + $conditions);
                $summaries         = array_merge($summaries, $iakFunkySummaries);
            }

            //Go through the summaries and attach to the corresponding product
            foreach($summaries as $summary){
                //Get the company corresponding to this product_id
                $companyName      = $productIds[$summary['product_id']]['company'];
                $stringIdentifier = $summary['product_id'];

                //Add the summary to the right spot
                $productsPerCompany[$companyName][$stringIdentifier][ResourceInterface::PRODUCT_SUMMARY] = $summary[ResourceInterface::PRODUCT_SUMMARY];
            }

            //We're done set the result
            //One object per company
            foreach($productsPerCompany as $company => $products){
                //Sort by price default ascending
                $toBeSorted = array_values($products);
                usort($toBeSorted, function ($a, $b) {
                    return $a['price_default'] - $b['price_default'];
                });

                $this->result['companies'][] = [
                    ResourceInterface::RESOURCE__ID => $companyIds[$company],
                    ResourceInterface::COMPANY      => $company,
                    ResourceInterface::IMAGE        => $companyImages[$company],
                    ResourceInterface::URL          => $companyUrls[$company],
                    ResourceInterface::CLICKABLE    => $companyClickables[$company],
                    'products'                      => $toBeSorted,
                    ResourceInterface::BIRTHDATE    => isset($this->params[ResourceInterface::BIRTHDATE]) ? $this->params[ResourceInterface::BIRTHDATE] : '1980-01-01',
                ];
            }
            $this->result['name'] = $coverage->name;
        }else{
            $this->result = [
                'name'      => 'Vergoedingen',
                'companies' => [],
            ];
        }

    }

    /**
     * IAK has data inconsistencies where they offer coverage for a category cost_type
     * unlike all the others.
     * @return bool
     */
    private function isIAKFunkyCostType()
    {
        if(isset($this->params[ResourceInterface::COST_TYPE_ID], $this->params[ResourceInterface::USER]) && $this->params[ResourceInterface::USER] == IAKHelper::USER_ID && isset($this->funkyCostTypes[$this->params[ResourceInterface::COST_TYPE_ID]])){
            return true;
        }
        return false;
    }
}