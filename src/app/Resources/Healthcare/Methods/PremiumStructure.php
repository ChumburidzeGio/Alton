<?php

namespace App\Resources\Healthcare\Methods;


use App\Helpers\IAKHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use DB;
use Illuminate\Support\Collection;

class PremiumStructure extends HealthcareAbstractRequest
{

    public function executeFunction()
    {
        $this->result = [];

        $price_components = [];

        $conditions = array_only($this->params, [ResourceInterface::WEBSITE, ResourceInterface::USER]);

        if(isset($this->params[ResourceInterface::PRODUCT_ID], $this->params[ResourceInterface::BIRTHDATE], $this->params[ResourceInterface::OWN_RISK])){
            //Get the product and add it to the result
            $product = head(ResourceHelper::callResource2('product.healthcare2018', [
                ResourceInterface::PRODUCT_ID => $this->params[ResourceInterface::PRODUCT_ID],
                ResourceInterface::BIRTHDATE => $this->params[ResourceInterface::BIRTHDATE],
                ResourceInterface::OWN_RISK => $this->params[ResourceInterface::OWN_RISK],
                ResourceInterface::COLLECTIVITY_ID => (int)array_get($this->params, ResourceInterface::COLLECTIVITY_ID, 0),
                ResourceInterface::PAYMENT_PERIOD => $this->params[ResourceInterface::PAYMENT_PERIOD],
            ] + $conditions));

            if ($product === false) {
                $this->setErrorString('Could not find product and premium.');
                return;
            }

            //Get the base id
            $base_product = head(ResourceHelper::callResource2('product.healthcare2018', [
                ResourceInterface::PRODUCT_ID => $this->getProductId($product['provider_id'], 'base', $product['base_id']),
                ResourceInterface::BIRTHDATE => $this->params[ResourceInterface::BIRTHDATE],
                ResourceInterface::OWN_RISK => $this->params[ResourceInterface::OWN_RISK],
                ResourceInterface::COLLECTIVITY_ID => (int)array_get($this->params, ResourceInterface::COLLECTIVITY_ID, 0),
                ResourceInterface::PAYMENT_PERIOD => $this->params[ResourceInterface::PAYMENT_PERIOD],
            ] + $conditions));

            //Calculate cost types if the input parameter is present
            $cost_type_ids = [];
            $cost_types = [];
            if(isset($this->params['cost_type_ids'])){
                foreach (explode(',', $this->params['cost_type_ids']) as $cost_type){
                    $cost_type_ids[] = head(explode('_', $cost_type));
                }
                $cost_types = ResourceHelper::callResource2('cost_types.healthcare2018', [
                    ResourceInterface::__ID => $cost_type_ids,
                    OptionsListener::OPTION_LIMIT => 99999,
                ] + $conditions);
                $cost_types = Collection::make($cost_types)->keyBy('__id');
            }
            //Only show label and cost_type_id
            $visible_summary_fields = ResourceInterface::LABEL . ','. ResourceInterface::COST_TYPE_ID;

            if (isset($base_product['title'])){
                $base_product['title'] .= ' (eigen risico &euro; '.$this->params[ResourceInterface::OWN_RISK].')';
            }

            $price_components[] = $base_product;

            if(isset($product['child_source_ids'])){
                //If the product has child source ids , split them and fetch those
                $additionals = explode(',', $product['child_source_ids']);
                array_walk($additionals, function( &$additional ) use($base_product){
                    $additional = $this->getProductId($base_product['provider_id'], 'additional', $additional);
                });

                $result = ResourceHelper::callResource2('product.healthcare2018', [
                    ResourceInterface::PRODUCT_ID => $additionals,
                    ResourceInterface::BIRTHDATE => $this->params[ResourceInterface::BIRTHDATE],
                    ResourceInterface::COLLECTIVITY_ID => (int)array_get($this->params, ResourceInterface::COLLECTIVITY_ID, 0),
                    ResourceInterface::GROUP_BY => 'single_product',
                    OptionsListener::OPTION_LIMIT => 99999,
                    ResourceInterface::PAYMENT_PERIOD => $this->params[ResourceInterface::PAYMENT_PERIOD],
                ] + $conditions);

                foreach ($result as $premium){

                    //Get the summaries of the additional product for the requested cost types
                    if(!empty($cost_type_ids)){
                        $product_summaries = ResourceHelper::callResource2('product_summaries.healthcare2018', [
                            ResourceInterface::PRODUCT_ID => $premium['__id'],
                            ResourceInterface::COST_TYPE_ID => $cost_type_ids,
                            OptionsListener::OPTION_VISIBLE => $visible_summary_fields
                        ] + $conditions);
                        $product_summaries = $this->enrichSummaries($product_summaries, $cost_types);
                        $premium[ResourceInterface::PRODUCT_SUMMARY] = $product_summaries;
                    }
                    $price_components[] = $premium;
                }
            }
        }

        if (!empty($this->params[ResourceInterface::HIDE_FREE_TOPPINGS])) {
            $price_components = array_filter($price_components, function ($p) {
                return !$p[ResourceInterface::IS_TOPPING] || $p[ResourceInterface::PRICE_ACTUAL] > 0;
            });
        }

        $this->result = $this->orderPriceComponents($price_components);
    }

    private function orderPriceComponents($price_components)
    {
        $typeToSorting = array_flip(array_keys(IndexProduct::$typeLabelMap));

        // To maintain index order, we need to remember it :(
        foreach ($price_components as $k => $v) {
            $price_components[$k]['_index'] = $k / count($price_components);
        }
        // Sort everything
        $price_components = array_sort($price_components, function ($price_component) use ($typeToSorting) {
            return $typeToSorting[$price_component['type']] + $price_component['_index'];
        });
        foreach ($price_components as $k => $v) {
            unset($price_components[$k]['_index']);
        }

        $products = array_filter($price_components, function ($item) { return !$item['is_topping']; });
        $toppings = array_filter($price_components, function ($item) { return $item['is_topping']; });

        // Insert toppings after first additional product (2nd normal product)
        array_splice($products, 2, 0, $toppings);

        return $products;
    }

    /**
     * @param $company_id
     * @param $coverage_type
     * @param $product_id
     *
     * @return string
     */
    private function getProductId($company_id, $coverage_type, $product_id)
    {
        $prefix     = ($company_id != IAKHelper::COMPANY_ID)
            ? ($coverage_type === 'base' ? 'H' : 'A') : '';
        $product_id = $prefix . $product_id;

        return $product_id;
    }

    private function enrichSummaries($product_summaries, $cost_types)
    {
        if(!empty($cost_types)){
            foreach ($product_summaries as &$product_summary){
                $cost_type = $cost_types->get($product_summary['cost_type_id']);
                if($cost_type){
                    $product_summary['cost_type'] = $cost_type['name'];
                }
            }
        }
        return $product_summaries;

    }
}