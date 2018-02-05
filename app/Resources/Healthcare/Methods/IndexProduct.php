<?php

namespace App\Resources\Healthcare\Methods;


use App\Exception\PrettyServiceError;
use App\Helpers\FactoryHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Models\Field;
use App\Resources\Healthcare\Healthcare;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class IndexProduct extends HealthcareAbstractRequest
{

    public static $typeLabelMap = [
        'base' => 'Basisverzekering',
        'additional' => 'Aanvullende verzekering',
        'aanvullend' => 'Aanvullende verzekering',
        'klasse' => 'Klassenverzekering',
        'buitenland' => 'Buitenlandverzekering',
        'fysio' => 'Fysiotherapieverzekering',
        'alternatief' => 'Alternatieve geneeswijzen',
        'calamiteiten' => 'Calamiteitenverzekering',
        'gezinsplanning' => 'Gezinsplanningverzekering',
        'therapie' => 'Therapieverzekering',
        'ortho' => 'Orthodontieverzekering',
        'optiek' => 'Brillenverzekering',
        'tand' => 'Tandverzekering',
        // IAK topping only
        'geboorte' => 'Geboortezorg',
    ];

    protected $premiumUniqueFields = [
        ResourceInterface::AGE_FROM,
        ResourceInterface::AGE_TO,
        ResourceInterface::OWN_RISK,
        ResourceInterface::CO_INSURED,
        ResourceInterface::COLLECTIVITY_ID,
    ];

    protected $premiumFields = [
        ResourceInterface::AGE_FROM => '<=',
        ResourceInterface::AGE_TO => '>',
        ResourceInterface::OWN_RISK => '=',
        ResourceInterface::CO_INSURED => '=',
        ResourceInterface::BASE_ID => '=',
        ResourceInterface::PRODUCT_ID => '=',
        ResourceInterface::COLLECTIVITY_GROUP_ID => '=',
        ResourceInterface::COLLECTIVITY_GROUP_ID_IAK => '=',
    ];

    protected $productFields = [
        ResourceInterface::COVERAGE_AREA => '>=',
        ResourceInterface::FREE_CHOICE => '>=',
    ];

    // Filled dynamically from resource field data
    protected $productFilterFields = [];

    protected $premiumMappedFields = [
        ResourceInterface::PRICE_DEFAULT,
        ResourceInterface::PRICE_3M,
        ResourceInterface::PRICE_6M,
        ResourceInterface::PRICE_12M,
        ResourceInterface::PRICE_BASE,
        ResourceInterface::OWN_RISK,
        ResourceInterface::DEAL,
        ResourceInterface::COLLECTIVITY_OFFER,
    ];

    protected $featureMappedFields = [
        'kenmerken',
        'extras',
    ];

    private $groupByMapping = [
        'single_product'  => 'product_id',
        'product'  => 'base_id',
        'provider' => 'provider_id'
    ];

    public function setParams(Array $params)
    {

        if(!isset($params[ResourceInterface::GROUP_BY])){
            $params[ResourceInterface::GROUP_BY] = 'product';
        }
        if(!isset($params[ResourceInterface::TYPE]) && !isset($params[ResourceInterface::PRODUCT_ID])){
            $params[ResourceInterface::TYPE] = 'base';
        }
        if (!empty($params[ResourceInterface::BIRTHDATE])) {
            try{
                $age = Healthcare::getAgeFromBirthdate($params[ResourceInterface::BIRTHDATE]);
            } catch(\Exception $e) {
                $this->addErrorMessage(ResourceInterface::BIRTHDATE,'invalid.birthdate','Ongeldige geboortedatum');
                return;
            }

            $params[ResourceInterface::AGE_FROM] = $age;
            $params[ResourceInterface::AGE_TO] = $age;
        }

        // Children under 18 are always co-insured, adults above 18 are never co-insured. (they might be in the future?)
        // Children can match both co_insured 0 or 1, so leave unspecified then.
        if (isset($params[ResourceInterface::AGE_TO]) && !isset($params[ResourceInterface::CO_INSURED]) && $params[ResourceInterface::AGE_TO] >= 18)
            $params[ResourceInterface::CO_INSURED] = 0;

        // Children never have 'own risk'
        if (isset($params[ResourceInterface::AGE_TO]) && $params[ResourceInterface::AGE_TO] < 18)
            unset($params[ResourceInterface::OWN_RISK]);

        // Make sure these are 0 instead of 'not set', so we filter
        if (empty($params[ResourceInterface::COLLECTIVITY_ID]) || $params[ResourceInterface::COLLECTIVITY_ID] == 'undefined')
            $params[ResourceInterface::COLLECTIVITY_ID] = 0;
        if (empty($params[ResourceInterface::COLLECTIVITY_GROUP_ID]))
            $params[ResourceInterface::COLLECTIVITY_GROUP_ID] = Healthcare::PREMIUM_GROUP_ID_KOMPARU_AFFILIATE;

        if (!empty($params[ResourceInterface::__ID]))
            $params[ResourceInterface::PRODUCT_ID] = $params[ResourceInterface::__ID];

        parent::setParams($params);
    }

    public function executeFunction()
    {
        $this->productFilterFields = $this->getProductFilterFields();

        // Map collectivity_id to its group collectivity (each group has the same premiums)
        if (!empty($this->params[ResourceInterface::COLLECTIVITY_ID])) {
            $cols = ResourceHelper::callResource2('collectivity.healthcare2018', ['__id' => $this->params[ResourceInterface::COLLECTIVITY_ID]]);
            if (!$cols) {
                $this->setErrorString('Unknown collectivity ID.');
                return;
            }
            // Collectivity groups take the cheapest from either "Zorgweb Products IAK discounts", or "IAK Product" premiums
            $this->params[ResourceInterface::COLLECTIVITY_GROUP_ID] = [head($cols)[ResourceInterface::COLLECTIVITY_GROUP_ID], head($cols)[ResourceInterface::COLLECTIVITY_GROUP_ID_IAK]];
        }

        /** @var Builder $query */
        $query = DB::connection('mysql_product')->table('product_healthcare2018');

        // Only fetch premium data if we have any premium fields as input
        $hasPremiumFilter = array_only(array_filter($this->params), $this->premiumUniqueFields) !== [];
        if ($hasPremiumFilter && empty($this->params[OptionsListener::OPTION_NO_PROPAGATION])) {
            if (array_diff($this->premiumUniqueFields, array_keys($this->params)) !== []) {
                // if we do not have all premium fields, we will need to group & get cheapest
                $premiumQuery = $this->getPremiumQueryByGroup();

                $query->join(DB::raw('('. $premiumQuery->toSql() .') AS premium_healthcare2018'),
                    'premium_healthcare2018.product_id', '=', 'product_healthcare2018.product_id'
                );
                $query->mergeBindings($premiumQuery);
            }
            else {
                // We have all premium info, a straight join will do
                $query->join('premium_healthcare2018',
                    'premium_healthcare2018.product_id', '=', 'product_healthcare2018.product_id'
                );

                foreach ($this->premiumFields as $fieldName => $operator) {
                    if (array_key_exists($fieldName, $this->params)) {
                        if ($fieldName == ResourceInterface::COLLECTIVITY_GROUP_ID) {
                            if (is_array($this->params[ResourceInterface::COLLECTIVITY_GROUP_ID]))
                                $query->whereIn('premium_healthcare2018.'. ResourceInterface::COLLECTIVITY_ID, $this->params[ResourceInterface::COLLECTIVITY_GROUP_ID]);
                            else
                                $query->where('premium_healthcare2018.'. ResourceInterface::COLLECTIVITY_ID, $this->params[ResourceInterface::COLLECTIVITY_GROUP_ID]);
                        }
                        else if (is_array($this->params[$fieldName]) && $operator == '=')
                            $query->whereIn('premium_healthcare2018.'. $fieldName, $this->params[$fieldName]);
                        else
                            $query->where('premium_healthcare2018.'. $fieldName, $operator, $this->params[$fieldName]);
                    }
                }
            }

            $query->select($this->getQueryOutputFields());
        }
        else
        {
            $query->select($this->getProductQueryOutputFields());
        }

        // Apply product-level filters
        foreach ($this->productFilterFields as $fieldName => $operator) {
            if (isset($this->params[$fieldName])) {
                // Temp hackfix
                if ($this->params[$fieldName] == '0' && $operator == '>=')
                    continue;

                if (is_array($this->params[$fieldName]) && $operator == '=')
                    $query->whereIn('product_healthcare2018.' . $fieldName, $this->params[$fieldName]);
                elseif (substr($this->params[$fieldName], 0, 1) === '!' && $operator == '='){
                    $query->where('product_healthcare2018.' . $fieldName, '!=', substr($this->params[$fieldName], 1));
                }
                else
                    $query->where('product_healthcare2018.' . $fieldName, $operator, $this->params[$fieldName]);
            }
        }

        // Add grouping, if necessary
        if (empty($this->params[ResourceInterface::PRODUCT_ID]) && (array_get($this->params, ResourceInterface::GROUP_BY) !== 'single_product') && $hasPremiumFilter) {
            $groupColumn = 'base_id';
            if(isset($this->params[ResourceInterface::GROUP_BY]) && isset($this->groupByMapping[$this->params[ResourceInterface::GROUP_BY]])){
                $groupColumn = $this->groupByMapping[$this->params[ResourceInterface::GROUP_BY]];
            }

            // Unless we have a product_id specified, we want to find the cheapest `price` per base product (`base_id`)
            $query = $this->getAggregateByGroup($query, 'product_healthcare2018', $groupColumn, $this->getPriceField(), 'MIN');
        }

        // Add sorting, if requested
        if (!empty($this->params[OptionsListener::OPTION_ORDER])) {
            if (in_array($this->params[OptionsListener::OPTION_ORDER], $this->getResourceOutputFields()))
                $query->orderBy(
                    $this->params[OptionsListener::OPTION_ORDER],
                    strtolower(array_get($this->params, OptionsListener::OPTION_DIRECTION, 'asc')) === 'asc' ? 'ASC' : 'DESC'
                );
        }

        $countQuery = clone $query;
        header("Total-Count-Internal: " . $countQuery->count());

        if (!empty($this->params[OptionsListener::OPTION_LIMIT]))
            $query->limit(min(1000, (int)$this->params[OptionsListener::OPTION_LIMIT]));
        if (!empty($this->params[OptionsListener::OPTION_OFFSET]))
            $query->offset((int)$this->params[OptionsListener::OPTION_OFFSET]);

        //If you have a collectivity, get the kenmerken into the result by joining
        if (!empty($this->params[ResourceInterface::COLLECTIVITY_ID])) {

            $query->leftJoin('features_healthcare2018', function($join)
            {
                //Eloquent does not maintain any semblance of order of the ? bindings
                //The if hell that ensues by not hardcoding the
                $join->on('features_healthcare2018.insurance_provider_id', '=', 'product_healthcare2018.provider_id')
                     ->on('features_healthcare2018.collectivity_id', '=', DB::raw('"'. (int)$this->params[ResourceInterface::COLLECTIVITY_ID] .'"'));
            });

            $selectFields = [];
            foreach ($this->featureMappedFields as $fieldName) {
                $selectFields[] = 'features_healthcare2018.'. str_replace('.', '_', $fieldName);
            }
            $query->addSelect($selectFields);
        }

        //        uncomment these to get yourself query with parameters instead of “?”
        //        $b = $query->getBindings();
        //        $q = preg_replace_callback('/\?/', function()use(&$b){return array_shift($b);}, $query->toSql());

        $products = $query->get()->toArray();

        // Because of the query's aggregate filtering, we may return multiple results per 'group_by', if they have the same price.
        // (this can happen when there are some free sub-products)
        // This method filters them, selecting the (first) one with the most 'products' in it.
        // (we could add a pseudo-price column with a unique (price + nr of products) sortable value to prevent this from happening)
        $products = $this->selectGroupBySamePriceBestProducts($products);

        $products = $this->mapTypeToLabel($products);

        $this->result = $products;
    }

    public function mapTypeToLabel($products)
    {
        foreach ($products as $key => $product) {
            if(isset(self::$typeLabelMap[$product->type])){
                $products[$key]->type_label = self::$typeLabelMap[$product->type];
            }else{
                $products[$key]->type_label = 'Verzekering';
            }
        }
        return $products;
    }

    /**
     * This method solves the 'greatest-n-per-group' SQL problem via the 'INNER JOIN' method.
     * See: https://stackoverflow.com/a/7745635/678265
     *
     * @param $query
     * @param $groupTable
     * @param $groupColumn
     * @param $aggregateColumn
     * @param string $aggregateFunction
     * @return mixed
     */
    protected function getAggregateByGroup(Builder $query, $groupTable, $groupColumn, $aggregateColumn, $aggregateFunction = 'MIN')
    {
        $groupQuery = clone $query;
        $groupQuery->select($groupTable .'.'. $groupColumn, DB::raw($aggregateFunction .'('. $aggregateColumn .') AS '. $aggregateColumn));
        $groupQuery->groupBy($groupTable .'.'. $groupColumn);

        /** @var Builder $comboQuery */
        $comboQuery = DB::connection('mysql_product')->table(DB::raw('(' . $query->toSql() . ') as '. $groupTable));
        $comboQuery->select($groupTable .'.*');
        $comboQuery->mergeBindings($query);

        $comboQuery->join(
            DB::raw('(' . $groupQuery->toSql() . ') as ' . $groupTable . '_grouped'),
            function (JoinClause $join) use ($groupColumn, $aggregateColumn, $groupTable) {
                $join->on($groupTable . '_grouped.' . $aggregateColumn, '=', $groupTable . '.' . $aggregateColumn);
                $join->on($groupTable . '_grouped.' . $groupColumn, '=', $groupTable . '.' . $groupColumn);
            }
        );
        $comboQuery->mergeBindings($groupQuery);

        return $comboQuery;
    }

    protected function getPremiumQueryByGroup()
    {
        // We do not have all premium info fields, so we need to select the cheapest via grouping subquery

        /** @var Builder $premiumQuery */
        $premiumQuery = DB::connection('mysql_product')->table('premium_healthcare2018');

        foreach ($this->premiumFields as $fieldName => $operator) {
            if (array_key_exists($fieldName, $this->params)) {
                if ($fieldName == ResourceInterface::COLLECTIVITY_GROUP_ID) {
                    if (is_array($this->params[ResourceInterface::COLLECTIVITY_GROUP_ID]))
                        $premiumQuery->whereIn(ResourceInterface::COLLECTIVITY_ID, $this->params[ResourceInterface::COLLECTIVITY_GROUP_ID]);
                    else
                        $premiumQuery->where(ResourceInterface::COLLECTIVITY_ID, $this->params[ResourceInterface::COLLECTIVITY_GROUP_ID]);
                }
                elseif (is_array($this->params[$fieldName]) && $operator == '=')
                    $premiumQuery->whereIn($fieldName, $this->params[$fieldName]);
                else
                    $premiumQuery->where($fieldName, $operator, $this->params[$fieldName]);
            }
        }

        return $this->getAggregateByGroup($premiumQuery, 'premium_healthcare2018', 'product_id', $this->getPriceField(), 'MIN');
    }

    protected function getResourceOutputFields()
    {
        $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', 'product.healthcare2018', false, true);

        $outputFields = [];
        foreach ($resource->fields as $field) {
            if ($field->output && in_array(Field::FILTER_PATCH, $field->filters))
                $outputFields[] = $field->name;
        }

        foreach ($this->premiumMappedFields as $fieldName) {
            $outputFields[] = $fieldName;
        }

        return $outputFields;
    }

    protected function getProductQueryOutputFields()
    {
        $resource = FactoryHelper::retrieveModel(\App\Models\Resource::class, 'name', 'product.healthcare2018', false, true);

        $outputFields = [];
        foreach ($resource->fields as $field) {
            if ($field->output && in_array(Field::FILTER_PATCH, $field->filters))
                $outputFields[] = 'product_healthcare2018.'. str_replace('.', '_', $field->name);
        }

        return $outputFields;
    }

    protected function getQueryOutputFields()
    {
        $outputFields = $this->getProductQueryOutputFields();

        foreach ($this->premiumMappedFields as $fieldName) {
            $outputFields[] = 'premium_healthcare2018.'. str_replace('.', '_', $fieldName);
        }

        $outputFields[] = 'premium_healthcare2018.'. $this->getPriceField() .' AS price_monthly';
        $outputFields[] = DB::raw('(premium_healthcare2018.'. $this->getPriceField() .' * '. $this->getPaymentPeriodMonths() .') AS price_actual');
        $outputFields[] = DB::raw('((premium_healthcare2018.'. $this->getPriceField() .' + '.  $this->getMonthlyDiscount() .') * '. $this->getPaymentPeriodMonths() .') AS price_before_discount');
        $outputFields[] = DB::raw('('. $this->getMonthlyDiscount() .' * '. $this->getPaymentPeriodMonths() .') AS price_discount');

        return $outputFields;
    }

    protected function getProductFilterFields()
    {
        $resource = FactoryHelper::retrieveModel(\App\Models\Resource::class, 'name', 'product.healthcare2018', false, true);

        $fields = [];
        foreach ($resource->fields as $field) {

            // Do not filter product on collectivity_id (only in premium?)
            if ($field->name == ResourceInterface::COLLECTIVITY_ID)
                continue;

            if (!$field->output && is_numeric(substr($field->name, 0, 2))) {
                // Coverage fields
                $fields[$field->name] = '>=';
            }
            else if ($field->output && in_array(Field::FILTER_PATCH, $field->filters)) {
                // Normal fields
                $fields[$field->name] = array_get($this->productFields, $field->name, '=');
            }
        }

        return $fields;
    }

    protected function getPriceField()
    {
        switch (array_get($this->params, ResourceInterface::PAYMENT_PERIOD, 1)) {
            case 1: return ResourceInterface::PRICE_DEFAULT;
            case 3: return ResourceInterface::PRICE_3M;
            case 6: return ResourceInterface::PRICE_6M;
            case 12: return ResourceInterface::PRICE_12M;
            default:
                throw new \Exception('Unknown payment period.');
        }
    }

    protected function getMonthlyDiscount()
    {
        switch (array_get($this->params, ResourceInterface::PAYMENT_PERIOD, 1)) {
            case 1: return '0';
            case 3: return 'IFNULL(premium_healthcare2018.discount_3m, 0)';
            case 6: return 'IFNULL(premium_healthcare2018.discount_6m, 0)';
            case 12: return 'IFNULL(premium_healthcare2018.discount_12m, 0)';
            default:
                throw new \Exception('Unknown payment period.');
        }
    }

    protected function getPaymentPeriodMonths()
    {
        switch (array_get($this->params, ResourceInterface::PAYMENT_PERIOD, 1)) {
            case 1: return 1;
            case 3: return 3;
            case 6: return 6;
            case 12: return 12;
            default:
                throw new \Exception('Unknown payment period.');
        }
    }

    protected function selectGroupBySamePriceBestProducts($products)
    {
        $groupByField = array_get($this->groupByMapping, $this->params[ResourceInterface::GROUP_BY], 'base_id');

        if ($groupByField == 'product_id')
            return $products;

        // Iterate through products and remember best of each 'group'
        $groupByProducts = [];
        $hasManyInGroup = false;
        foreach ($products as $key => $product) {

            if (!isset($groupByProducts[$product->{$groupByField}])) {
                $groupByProducts[$product->{$groupByField}] = $product;
            }
            else {
                // We count nr of products by looking at the comma-separated field 'child_source_ids' (which may be empty)
                if (count(array_filter(explode(',', $product->child_source_ids))) > count(array_filter(explode(',', $groupByProducts[$product->{$groupByField}]->child_source_ids))))
                    $groupByProducts[$product->{$groupByField}] = $product;

                $hasManyInGroup = true;
            }
        }

        if (!$hasManyInGroup)
            return $products;

        // Filter out any products in a group which are not the 'best' (aka with the most products)
        foreach ($products as $key => $product) {
            if ($groupByProducts[$product->{$groupByField}]->product_id !== $product->product_id) {
                unset($products[$key]);
            }
        }
        return array_values($products);
    }
}