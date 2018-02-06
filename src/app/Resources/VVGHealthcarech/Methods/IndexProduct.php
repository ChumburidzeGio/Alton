<?php

namespace App\Resources\VVGHealthcarech\Methods;


use App\Interfaces\ResourceInterface;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use App\Resources\VVGHealthcarech\VVGHealthcarech;
use Illuminate\Support\Facades\DB;

class IndexProduct extends HealthcareAbstractRequest
{
    const COVERAGE_NAME_TO_ID = [
        ResourceInterface::ALTERNATIVE_MEDICINE => 1,
        ResourceInterface::GLASSES => 2,
        ResourceInterface::EMERGENCIES_ABROAD => 3,
        ResourceInterface::PRECAUTION => 6,
        ResourceInterface::AID => 8,
        ResourceInterface::DENTAL_TREATMENTS => 9,
        ResourceInterface::ORTHODONTICS => 10,
        ResourceInterface::SUPPLEMENTARY_HOSPITAL => 11,
        ResourceInterface::SEARCH_RESCUE => 12
    ];

    const COVERAGE_TO_OPERATOR = [
        11 => '<=',
    ];
    const COVERAGE_TO_VALUE_TYPE_ID = [
        3 => 2, // Emergencies broad: 'schweiz' & 'world'
    ];
    const COVERAGE_TO_DEFAULT_VALUE = [
        11 => 99999, // Supplementary hospital, reverse sorting (99999 is 'none')
    ];
    // Which coverages should have the input franchise added
    const COVERAGE_HAS_FRANCHISE = [
        11 => true, // Supplementary hospital, reverse sorting (99999 is 'none')
    ];

    protected static $valueTypeToNumber = [
        2 => [
            'schweiz' => 0,
            'world' => 1,
        ],
    ];

    public function setParams(Array $params)
    {
        if( ! empty($params[ResourceInterface::BIRTHDATE])){
            $age = VVGHealthcarech::getAgeFromBirthdate($params[ResourceInterface::BIRTHDATE]);

            $params[ResourceInterface::AGE] = $age;
        }

        $coverage = [];

        foreach(self::COVERAGE_NAME_TO_ID as $key => $index) {
            if (isset($params[$key])) {
                $coverage[$index] = $params[$key];
                unset($params[$key]);
            }
        }

        $params[ResourceInterface::COVERAGE] = $coverage;

        parent::setParams($params);
    }

    public function executeFunction()
    {
        if (empty($this->params[ResourceInterface::COVERAGE]) && empty($this->params[ResourceInterface::__ID])) {
            $this->setErrorString('No coverages specified.');
            return;
        }

        // We know which product combo we want exactly...
        $productFilter = '';
        $requestedProductIds = [];
        if (!empty($this->params[ResourceInterface::__ID])) {
            $requestedProductIds = array_map('intval', explode('_', $this->params[ResourceInterface::__ID]));
            $productFilter = ' AND prod.product_id IN ('. implode(',', $requestedProductIds) .') ';
        }

        // Get all coverages
        $companyCoverageProducts = [];
        $matchedProducts = [];
        $matchedProductIds = [];
        $requestedCoverage = [];
        foreach (self::COVERAGE_NAME_TO_ID as $coverageId) {

            // Get coverage from params
            $coverageValue = array_get($this->params[ResourceInterface::COVERAGE], $coverageId);
            $coverageValueNumber = $coverageValue === null ? array_get(self::COVERAGE_TO_DEFAULT_VALUE, $coverageId, 0) : self::castCoverageValueToNumber(array_get(self::COVERAGE_TO_VALUE_TYPE_ID, $coverageId, 1), $coverageValue);

            // Remember if this coverage is 'requested' by having a non-default value as input
            $requestedCoverage[$coverageId] = $coverageValueNumber !== array_get(self::COVERAGE_TO_DEFAULT_VALUE, $coverageId, 0);

            // Fixed query params
            $queryParams = [
                $this->params[ResourceInterface::AGE],
                $coverageId,
                $coverageValueNumber,
            ];

            $valueQuery = ' AND data.value '. array_get(self::COVERAGE_TO_OPERATOR, $coverageId, '>=') .' ? ';

            $queryString = <<<SQL
                SELECT 
                    comp.abreviation,
                    comp.knip_id,
                    comp.image,
                    prod.title,
                    prod.description,
                    prod.category_id,
                    prod.sub_id,
                    data.product_id,
                    data.value,
                    data.value_type_id,
                    data.percentage,
                    data.years,
                    data.days,
                    data.waiting_period_months,
                    data.franchise as coverage_franchise,
                    data.comments,
                    data.label,
                    data.coverage_id,
                    coverage.category_id as coverage_category_id,
                    coverage.title as coverage_title,
                    coverage.description as coverage_description,
                    coverage.icon as coverage_icon,
                    category.title as category_title,
                    category.description as category_description,
                    coverage_category.title as coverage_category_title,
                    coverage_category.description as coverage_category_description
                FROM product_vvghealthcarech AS prod
                INNER JOIN data_vvghealthcarech AS `data`
                    ON prod.`product_id` = `data`.product_id
                INNER JOIN models_company comp
                    ON comp.knip_id = prod.knip_id
                INNER JOIN coverage_vvghealthcarech coverage
                    ON coverage.id = data.coverage_id
                LEFT JOIN category_vvghealthcarech category
                    ON category.id = prod.category_id
                LEFT JOIN category_vvghealthcarech coverage_category
                    ON coverage_category.id = coverage.category_id
                WHERE TRUE
                      AND prod.active = 1
                      AND ? BETWEEN data.age_from AND data.age_to
                      AND data.coverage_id = ?
                      $valueQuery
                      $productFilter
                GROUP BY prod.`__id`;
SQL;
            $coverageProducts = DB::connection('mysql_product')->select($queryString, $queryParams);

            foreach($coverageProducts as $coverageProduct){
                // Tweak data for easier use later
                $coverageProduct->requested_coverage = $requestedCoverage[$coverageId];

                // Initialize company array
                if (!isset($companyCoverageProducts[$coverageProduct->knip_id])) {
                    foreach (array_values(self::COVERAGE_NAME_TO_ID) as $name => $subCoverageId)
                        $companyCoverageProducts[$coverageProduct->knip_id][$subCoverageId] = [];
                }

                if (!$requestedCoverage[$coverageId] && $coverageProduct->value === array_get(self::COVERAGE_TO_DEFAULT_VALUE, $coverageId, 0) && !$coverageProduct->comments) {
                    // Do not remember 'empty coverages'
                }
                else
                {
                    // Remember coverage per product
                    $coverageProduct->coverage_name = array_search($coverageId, self::COVERAGE_NAME_TO_ID);
                    $companyCoverageProducts[$coverageProduct->knip_id][$coverageId][$coverageProduct->product_id] = array_only(
                        get_object_vars($coverageProduct),
                        ['coverage_id', 'product_id', 'category_id', 'value', 'value_type_id', 'percentage', 'years', 'days', 'waiting_period_months', 'franchise', 'coverage_franchise', 'requested_coverage', 'coverage_name', 'label', 'contract_duration', 'comments', 'coverage_title', 'coverage_description', 'coverage_icon', 'coverage_category_id', 'coverage_category_description']
                    );
                }

                // Remember matched product per company
                if ($requestedCoverage[$coverageId] || in_array($coverageProduct->product_id, $requestedProductIds)) {
                    $matchedProducts[$coverageProduct->knip_id][$coverageProduct->product_id] = get_object_vars($coverageProduct);
                    $matchedProductIds[$coverageProduct->product_id] = $coverageProduct->knip_id;
                }
            }
        }

        // Fetch premiums
        $productIdsPart = $matchedProductIds ? implode(',', array_keys($matchedProductIds)) : '0';
        $queryParams = [
            $this->params[ResourceInterface::AGE],
            (int)array_get($this->params, ResourceInterface::CALCULATION_CONTRACT_DURATION, 1),
            (int)array_get($this->params, ResourceInterface::ACCIDENT, 0),
            (int)array_get($this->params, ResourceInterface::CALCULATION_FRANCHISE, 0),
            array_get($this->params, ResourceInterface::GENDER, 'male'),
            $this->params[ResourceInterface::AGE],
            (int)array_get($this->params, ResourceInterface::CALCULATION_CONTRACT_DURATION, 1),
            (int)array_get($this->params, ResourceInterface::ACCIDENT, 0),
            (int)array_get($this->params, ResourceInterface::CALCULATION_FRANCHISE, 0),
            array_get($this->params, ResourceInterface::GENDER, 'male'),
        ];
        $queryString = <<<SQL
                SELECT 
                    prem.product_id,
                    prem.price, 
                    prem.has_parent_with_same_coverage, 
                    prem.accident, 
                    prem.franchise,
                    prem.years as contract_duration
                FROM premium_vvghealthcarech AS prem
                INNER JOIN (
                    SELECT MIN(price) as min_price 
                    FROM premium_vvghealthcarech as prem
                    WHERE
                        TRUE
                        AND prem.product_id IN ($productIdsPart)
                        AND ? BETWEEN prem.age_from AND prem.age_to
                        AND (prem.years <= ? OR prem.years IS NULL)
                        AND (prem.accident = ? OR prem.accident IS NULL)
                        AND (prem.franchise <= ? OR prem.franchise IS NULL)
                        AND (prem.has_parent_with_same_coverage = 0 OR prem.has_parent_with_same_coverage IS NULL)
                        AND (prem.gender = ? OR prem.gender IS NULL)
                    GROUP BY product_id
                ) as prem2 ON (prem.price = min_price)
                WHERE TRUE
                      AND prem.product_id IN ($productIdsPart)
                      AND ? BETWEEN prem.age_from AND prem.age_to
                      AND (prem.years <= ? OR prem.years IS NULL)
                      AND (prem.accident = ? OR prem.accident IS NULL)
                      AND (prem.franchise <= ? OR prem.franchise IS NULL)
                      AND (prem.has_parent_with_same_coverage = 0 OR prem.has_parent_with_same_coverage IS NULL)
                      AND (prem.gender = ? OR prem.gender IS NULL);
SQL;
        $premiumData = DB::connection('mysql_product')->select($queryString, $queryParams);
        foreach ($premiumData as $premium) {
            $premium->price = (float)$premium->price;
            $matchedProducts[$matchedProductIds[$premium->product_id]][$premium->product_id] = array_merge($matchedProducts[$matchedProductIds[$premium->product_id]][$premium->product_id], get_object_vars($premium));
        }
        // Remove all products with no price
        foreach ($matchedProducts as $knipId => $products) {
            $matchedProducts[$knipId] = array_filter($products, function ($a) { return isset($a['price']); });
        }


        // Get our combos
        if ($requestedProductIds) {
            // We know what we want...
            if (count($companyCoverageProducts))
            {
                $knipId = head(array_keys($companyCoverageProducts));
                $comboPerCompany = [$knipId => ['products' => $requestedProductIds]];
            }
            else
            {
                $comboPerCompany = [];
            }
        }
        else {
            // Or else find the cheapest combos
            $comboPerCompany = $this->findCoverageCombos($matchedProducts, $companyCoverageProducts, $requestedCoverage);
        }

        // Expand combo data with compound ID / Title + products & coverage data
        $results = [];
        foreach($comboPerCompany as $knipId => $combo){

            // Sort by category
            $combo['products'] = array_sort($combo['products'], function ($a) use ($matchedProducts, $knipId) {
                return isset($matchedProducts[$knipId][$a]) ? $matchedProducts[$knipId][$a]['category_id'] : null;
            });

            // Build product combo data
            $comboProduct = null;
            foreach ($combo['products'] as $productId) {
                $product = $matchedProducts[$knipId][$productId];
                if (!$comboProduct) {
                    $comboProduct = [
                        ResourceInterface::__ID => $product['product_id'],
                        'company' => [
                            'name' => $product['abreviation'],
                            'image' => $product['image'],
                        ],
                        ResourceInterface::KNIP_ID => $product['knip_id'],
                        ResourceInterface::TITLE => $product['title'],
                        ResourceInterface::PRICE_ACTUAL => 0,
                        ResourceInterface::CONTRACT_DURATION => $product['contract_duration'],
                        ResourceInterface::FRANCHISE => $product['franchise'],
                    ];
                }
                else {
                    $comboProduct[ResourceInterface::__ID] .= '_'. $product['product_id'];
                    $comboProduct[ResourceInterface::TITLE] .= ' + '. $product['title'];
                    $comboProduct[ResourceInterface::CONTRACT_DURATION] = max($comboProduct[ResourceInterface::CONTRACT_DURATION], $product['contract_duration']);
                    $comboProduct[ResourceInterface::FRANCHISE] = max($comboProduct[ResourceInterface::FRANCHISE], $product['franchise']);
                }
                $comboProduct[ResourceInterface::PRICE_ACTUAL] += (float)$product['price'];
                // Add array with info of all products in combo
                $subProduct = array_only(
                    $product,
                    [ResourceInterface::PRODUCT_ID, ResourceInterface::TITLE, ResourceInterface::CATEGORY_ID, ResourceInterface::PRICE, ResourceInterface::DESCRIPTION, ResourceInterface::CATEGORY_DESCRIPTION, ResourceInterface::FRANCHISE, ResourceInterface::ACCIDENT, ResourceInterface::CONTRACT_DURATION]
                );
                // Add all coverages for this product
                $subProduct[ResourceInterface::CATEGORIES] = [];
                foreach (self::COVERAGE_NAME_TO_ID as $coverageName => $coverageId) {
                    if (isset($companyCoverageProducts[$knipId][$coverageId][$productId])) {
                        $coverage = $companyCoverageProducts[$knipId][$coverageId][$productId];
                        if (!isset($subProduct[ResourceInterface::CATEGORIES][$coverage['coverage_category_id']])) {
                            $subProduct[ResourceInterface::CATEGORIES][$coverage['coverage_category_id']] = [
                                ResourceInterface::TITLE => $coverage['coverage_category_description'],
                                ResourceInterface::COVERAGES => [],
                            ];
                        }
                        $subProduct[ResourceInterface::CATEGORIES][$coverage['coverage_category_id']][ResourceInterface::COVERAGES][] = $companyCoverageProducts[$knipId][$coverageId][$productId];
                    }
                    /*
                    if (isset($companyCoverageProducts[$knipId][$coverageId][$productId])) {
                        if (isset($comboProduct[ResourceInterface::COVERAGES][$coverageId])) {
                            dd($comboProduct[ResourceInterface::COVERAGES][$coverageId], $companyCoverageProducts[$knipId][$coverageId][$productId]);
                            // This combo already has this coverage? Select the best one to show
                            switch (array_get(self::COVERAGE_TO_OPERATOR, 'coverageId')) {
                                default:
                                case '>=':
                                    if ($comboProduct[ResourceInterface::COVERAGES][$coverageId]['value'] > $companyCoverageProducts[$knipId][$coverageId][$productId]['value'])
                                        continue;
                                    break;
                                case '<=':
                                    if ($comboProduct[ResourceInterface::COVERAGES][$coverageId]['value'] < $companyCoverageProducts[$knipId][$coverageId][$productId]['value'])
                                        continue;
                                    break;
                            }
                        }
                        $comboProduct[ResourceInterface::COVERAGES][$coverageId] = $companyCoverageProducts[$knipId][$coverageId][$productId];
                    }
                    */
                }
                ksort($subProduct[ResourceInterface::CATEGORIES]);
                $subProduct[ResourceInterface::CATEGORIES] = array_values($subProduct[ResourceInterface::CATEGORIES]);
                $comboProduct[ResourceInterface::SUB_PRODUCTS][] = $subProduct;
            }

            // Sort products
            $comboProduct[ResourceInterface::SUB_PRODUCTS] = array_values(array_sort($comboProduct[ResourceInterface::SUB_PRODUCTS], function ($a) { return $a[ResourceInterface::CATEGORY_ID]; }));

            // Sort coverages by category of product
            //$comboProduct[ResourceInterface::COVERAGES] = array_values(array_sort($comboProduct[ResourceInterface::COVERAGES], function ($a) { return $a['category_id']; }));

            $results[] = $comboProduct;
        }

        // Sort all combos by price, ascending
        $this->result = array_values(array_sort($results, function ($a) { return $a[ResourceInterface::PRICE_ACTUAL]; }));
    }

    public static function castCoverageValueToNumber($valueTypeId, $value)
    {
        if (isset(self::$valueTypeToNumber[$valueTypeId])) {
            if (!isset(self::$valueTypeToNumber[$valueTypeId][$value]))
                throw new \Exception('Unknown value `'. $value .'` for Knip VVG value type `'.$valueTypeId .'`.');

            return (int)self::$valueTypeToNumber[$valueTypeId][$value];
        }

        return (int)$value;
    }

    public static function coverageNumberToString($valueTypeId, $value)
    {
        if (isset(self::$valueTypeToNumber[$valueTypeId])) {
            if (!in_array((int)$value, self::$valueTypeToNumber[$valueTypeId], true))
                throw new \Exception('Unknown number `'. $value .'` for Knip VVG value type `'.$valueTypeId .'`.');

            return array_search((int)$value, self::$valueTypeToNumber[$valueTypeId], true);
        }

        return $value;
    }

    /*
     *  Search algorithm: for each product that matches any of our coverages, we check if it satisfies all coverages.
     *  If it does not satisfy all coverages, keep adding the cheapest product that will add a coverage we want.
     *  (because we sorted by price and checking only cheaper, time complexity is not ~O(n^3), but ~O(n log n))
     *
     *  Todo: Double check time complexity
     */
    protected function findCoverageCombos($matchedProducts, $companyCoverageProducts, $requestedCoverage)
    {
        cws('vvg_create_possible_combinations');

        // Sort all matching products by price
        foreach ($matchedProducts as $knipId => $products) {
            $matchedProducts[$knipId] = array_sort($products, function ($a) { return $a['price']; });
        }

        // Create possible combinations per company
        $comboPerProduct = [];
        foreach ($matchedProducts as $knipId => $products) {
            $processedProductIds = [];
            $comboPerProduct[$knipId] = [];
            foreach ($products as $productId => $product) {
                $combo = [
                    'products' => [$productId],
                    'categories' => [$product['category_id']],
                    'price' => (float)$product['price'],
                ];
                $processedProductIds[] = $productId;
                foreach ($companyCoverageProducts[$knipId] as $coverageId => $coveragesData) {
                    // This is not a requisted coverage - so ignore it
                    if (empty($requestedCoverage[$coverageId]))
                        continue;

                    $productsWithCoverage = array_keys($coveragesData);

                    // No products at all with the coverage we want? skip combo
                    if ($productsWithCoverage === []) {
                        continue 2;
                    }

                    if (array_intersect($combo['products'], $productsWithCoverage) === []) {
                        // None of the current products in the combo support this coverage. We include the cheapest (first) product that has the coverage.
                        $nextProductIds = array_diff($productsWithCoverage, $processedProductIds);
                        if (!$nextProductIds) {
                            // No other products with coverage? skip combo.
                            continue 2;
                        }
                        $found = false;
                        foreach ($nextProductIds as $nextProductId) {
                            if (!isset($products[$nextProductId]) || in_array($products[$nextProductId]['category_id'], $combo['categories'])) {
                                // We don't want to get product within any category we already have
                                // TODO: This may be incorrect! We should be able to upgrade within category? Assumes within category is always an upgrade?
                                continue;
                            }

                            $combo['products'][] = $nextProductId;
                            $combo['categories'][] = $products[$nextProductId]['category_id'];
                            $combo['price'] += $products[$nextProductId]['price'];
                            $found = true;
                        }
                        if (!$found) {
                            continue 2;
                        }
                    }
                }

                $comboPerProduct[$knipId][$productId] = $combo;
            }

            // Sort combos by price
            $comboPerProduct[$knipId] = array_sort($comboPerProduct[$knipId], function ($a) { return $a['price']; });

            // Take cheapest combo per company, if it exists
            if (!count($comboPerProduct[$knipId]))
                unset($comboPerProduct[$knipId]);
            else
                $comboPerProduct[$knipId] = head($comboPerProduct[$knipId]);
        }
        cwe('vvg_create_possible_combinations');

        return $comboPerProduct;
    }
}