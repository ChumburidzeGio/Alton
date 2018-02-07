<?php
namespace App\Resources\Allinone\Methods;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\Allinone\AbstractAllinoneRequest;
use Illuminate\Support\Facades\DB;

/**
 * Get carinsurance product information, including possible coverage combinations, possible own risk values and possible mileage values.
 */
class Request extends AbstractAllinoneRequest
{
    const FIRST_PAGE_FLAG = 'primary';
    const GENERIC = '_generic';

    public function executeFunction()
    {
        $insurances = $this->getInsurances();

        $fields = $this->getFields($insurances); // TODO should be cached

        if ($this->params[ResourceInterface::MODE] === '_first') {
            $this->params[ResourceInterface::MODE] = head($insurances);
        }

        foreach($insurances as $index => $insurance) {
            $insurances[$index][ResourceInterface::FORM] = $this->getForm($insurance, $fields); // TODO should be cached
            if ($this->params[ResourceInterface::MODE] !== '_prepare') {
                $insurances[$index][ResourceInterface::PRODUCTS] = $this->getProducts($insurance);
            }
        }

        $totalPrice = $this->calculateTotalPrice();

        $this->result = [
            ResourceInterface::PRICE_ACTUAL => $totalPrice,
            //ResourceInterface::PAYMENT_PERIOD => $this->params[ResourceInterface::PAYMENT_PERIOD],
            ResourceInterface::GENERIC_FORM => $fields[self::GENERIC],
            ResourceInterface::INSURANCES => $insurances,
        ];

    }

    protected function getInsurances()
    {
        $insurances = ResourceHelper::callResource2('insurances.allinone', ['_order' => 'order', '_direction' => 'asc']);

        if(!empty($this->params['select_insurances'])){
            $insurances = array_filter($insurances, function($item){
                return in_array($item['name'], $this->params['select_insurances']);
            });
        }

        return $insurances;
    }

    protected function getFields(array $insurances)
    {
        $resourceNames = array_map(function($insurance){
            return $insurance['product_resource_name'];
        }, $insurances);

        $fields = DB::table('resources')
                    ->join('fields', 'resources.id', '=', 'fields.resource_id')
                    ->leftJoin('resources AS cfr', 'cfr.id', '=', 'fields.get_values_from')
                    ->where('input', 1)
                    ->where('overview', 1)
                    ->whereIn('resources.name', $resourceNames)
                    ->select('fields.id AS id', 'fields.name AS name', 'fields.label AS label', 'fields.tags AS tags', 'fields.type AS type', 'fields.input_default AS default', 'rules', 'cfr.name AS choices_from', 'resources.name AS resource_name')
                    ->get();

        // convert the choices
        $fields = array_map(function($field){
            if ($field->type === 'Choice') {
                // if it's of type choice it should have a choice rule, and here we decode it
                $rules = explode('|', $field->rules);
                $field->options = array_map(function($rule){
                    $rule = trim($rule);
                    list($ruleName, $options) = explode(':', $rule);
                    if ($ruleName === 'choice') {
                        $options = explode(',', $options);
                        return array_map(function($option){
                            list($optionName, $optionLabel) = explode('=', $option);
                            if (!isset($optionLabel)) {
                                $optionLabel = $optionName;
                            }
                            return ['name' => $optionName, 'label' => $optionLabel];
                        }, $options);
                    }
                }, $rules);

            } else if (isset($field->choices_from)) {
                $field->type = 'Choice';
                $field->options = ResourceHelper::callResource2($field->choices_from);
            }
            unset($field->choices_from, $field->rules);
            return $field;
        }, $fields);

        // split the field in generic fields (for the first page) and the rest
        $reducedFields = array_reduce($fields, function($filter, $field){
            $tags = isset($field->tags) ? json_decode($field->tags) : [];
            unset($field->tags);
            if (in_array(self::FIRST_PAGE_FLAG, $tags)) {
                $filter[self::GENERIC][] = $field;
                return $filter;
            } else {
                if (!isset($filter[$field->resource_name])) {
                    $filter[$field->resource_name] = [];
                }
                $filter[$field->resource_name][] = $field;
                return $filter;
            }
        }, [self::GENERIC => []]);

        return $reducedFields;
    }

    protected function calculateTotalPrice()
    {
        return 0;
    }

    protected function getForm($insurance, array $fields)
    {
        return $fields[$insurance['product_resource_name']];
    }

    protected function getProducts($insurance)
    {
        // run the get products if the __id is set or the mode is on this insurance
        if (isset($this->params[$insurance][ResourceInterface::__ID]) || $this->params[ResourceInterface::MODE] === $insurance){
            return ResourceHelper::callResource2($insurance['product_resource_name']);
        }

        return null;
    }
}