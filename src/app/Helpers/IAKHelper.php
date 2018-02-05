<?php
/**
 * Created by PhpStorm.
 * User: kostya
 * Date: 13/11/15
 * Time: 09:36
 */

namespace App\Helpers;


use App\Commands\ImportIAKCommand;
use App\Interfaces\ResourceInterface;
use App\Models\Click;
use App\Models\Sale;
use DateTime;
use DB;
use Exception;
use IAKZorgwebDiscountsDataSeeder;
use Komparu\Document\Exception\DocumentNotFound;
use App, Config, Log;
use App\Resources\Healthcare\Healthcare;

/**
 * Class IAKHelper
 * @package App\Helpers
 */
class IAKHelper
{
    /**
     * IAK prodyct_type_id for basic insurances
     */
    const BASIC = [213371, 213375, 213377, 51325547];

    const USER_ID = 4261;
    const IAK_URL_IDENTIFIER = 100557675;


    /**
     * Company ID of IAK
     */
    const COMPANY_ID = 202901;

    private static $firstOfYear;

    /**
     * IAK prodyct_type_id for additional insurances
     */
    const SUB = [
        'aanvullend' => 213373,
        'klasse'     => 1478814,
        'tand'       => 1478815,
        'combo'      => 3251442
    ];

    const IAK_99999 = [98720901, 98720899, 98720902, 98720900, 98720903];

    const IAK_IDS = [
        202660 => [96919015, 98966248, 97472714, 97472715, 97472710, 98959878, 98966246, 96919012, 96919008, 98966247, 96919009, 96919010, 98966245, 99611907, 96919007, 96919011, 96919014],
        202669 => [
            99594121,
            96118969,
            96118965,
            96118975,
            96118972,
            96118966,
            96118973,
            96118971,
            96118964,
            96118968,
            98720900,
            98720903,
            98720902,
            98720899,
            98720901,
            96118974,
            96118970
        ],
        202686 => [
            97463287,
            98074307,
            96881981,
            96881982,
            96881985,
            96881979,
            96881980,
            96881987,
            99608943,
            96881978,
            96881977,
            96881989,
            96881983
        ],
        202704 => [
            96962691,
            96962686,
            99617835,
            96962680,
            96962701,
            96962681,
            96962706,
            96962705,
            96962689,
            96962703,
            96962694,
            96962707,
            96962700
        ],
        202736 => [
            96821079,
            96821084,
            96821053,
            98046605,
            98046606,
            98046604,
            96821067,
            96821062,
            96821064,
            96821072,
            96821078,
            96821065
        ]
    ];

    const IAK_99999_OWN_RISK = [
        //96118969
        59876392 => [385 => 96.23, 885 => 78.73],
        //96118965
        59876393 => [385 => 99.70, 485 => 96.70, 585 => 93.70, 685 => 90.70, 785 => 87.70, 885 => 82.20],
        //99594121
        59876384 => [385 => 105.16, 485 => 102.16, 585 => 99.16, 685 => 96.16, 785 => 93.16, 885 => 87.66]
    ];

    const COMPANY_RATINGS = [
        202901  => 8.8,
        202686  => 7.9,
        202704  => 7.9,
        202736  => 7.4,
        202669  => 6.9,
        202671  => 6.9,
        6654595 => 6.3,
        202658  => 6.3,
        202660  => 5.7,
        202663  => 5.3,
    ];

    const AI_MAP = [
        96821053 => 96821079,       // menzis cast all children everything to 750
        96821084 => 96821079,       // menzis same
        98259225 => null,           // anderzorg tand -> skip no tand
        98250114 => 98250105,       // anderzorg Jong -> Extra (for kids)
        98250110 => null            // anderzorg Budget -> nothing

    ];

    const MIN_AGE = 0;
    const MAX_AGE = 150;

    public static $iak2017toiak2018productIds = [
        96919014 => 118742357,
        96919011 => 118742345,
        96919007 => 118742343,
        99611907 => 118742353,
        98966245 => 118742328,
        96919010 => 118742336,
        //96919009 => ,
        98966247 => 118742356,
        96919008 => 118742329,
        96919012 => 118742347,
        98966246 => 118742340,
        98959878 => 118742338,
        97472710 => 118742342,
        97472715 => 118742334,
        97472714 => 118742337,
        98966248 => 118742335,
        96919015 => 118742331,
        96118970 => 118498284,
        96118974 => 118498270,
        98720901 => 121819508,
        98720899 => 121819505,
        98720902 => 121819491,
        98720903 => 121819483,
        98720900 => 121819480,
        96118968 => 118498279,
        96118964 => 118498269,
        96118971 => 118498274,
        96118973 => 118498277,
        96118966 => 118498272,
        96118972 => 118498268,
        96118975 => 118498271,
        96118965 => 118498280,
        96118969 => 118498281,
        99594121 => 118498278,
        96881983 => 118706965,
        96881989 => 118706962,
        96881977 => 118706961,
        96881978 => 118706968,
        99608943 => 118706959,
        96881987 => 118706957,
        96881980 => 118706967,
        96881979 => 118706960,
        96881985 => 118706956,
        96881982 => 118706964,
        96881981 => 118706966,
        98074307 => 118706963,
        97463287 => 118706958,
        96962700 => 118893176,
        96962707 => 118893180,
        96962694 => 118893173,
        96962703 => 118893169,
        96962689 => 118893172,
        96962705 => 118893181,
        96962706 => 118893170,
        96962681 => 118893179,
        96962701 => 118893174,
        96962680 => 118893171,
        99617835 => 118893178,
        96962686 => 118893182,
        96962691 => 118893177,
        96821065 => 118637469,
        96821078 => 118637479,
        96821072 => 118637460,
        96821064 => 118637478,
        96821062 => 118637461,
        96821060 => 118637495,
        96821054 => 118637462,
        96821068 => 118637476,
        96821059 => 118637487,
        96821081 => 118637471,
        96821083 => 118637493,
        96821063 => 118637480,
        96821057 => 118637485,
        96821074 => 118637449,
        96821067 => 118637491,
        98046604 => 118637473,
        98046606 => 118637446,
        98046605 => 118637451,
        96821053 => 118637468,
        96821084 => 118637467,
        96821079 => 118637464
    ];


    /**
     * Gets age ranges from additional insurance products and creates array of products
     *
     * @param $product
     *
     * @return array
     */
    public static function splitByAdditionalInsuranceAgeRange($product)
    {
        $products                             = [];
        $base_product                         = $product;
        $base_product['additional_insurance'] = [];

        // gluing additional products if age_groups are different but prices are equal
        $product['additional_insurance'] = self::glueAdditionalProducts($product['additional_insurance']);

        // get age group borders to
        $borders = self::getAgeBorders($product['additional_insurance']);

        $age_groups = self::groupByAgeRanges($product['additional_insurance'], $borders);

        $composers = [];

        $age_groups = array_map(function ($ais, $key) use (&$composers) {
            $ais = self::groupAdditionalInsurancesByType($ais, self::GROUP_VERBOSE_KEY, self::GROUP_ADD_NULL);

            $composers[$key] = self::buildComposer($ais);

            $ais = MathHelper::cartesian($ais);

            array_walk($ais, function (&$c) {
                $c = array_filter($c);
                if(array_key_exists('combi', $c)){
                    unset($c['aanvullend']);
                    unset($c['tand']);
                }
            });

            $ais = array_filter($ais, function ($c) {
                static $ids = [];
                $key    = implode('-', array_map(function ($p) {
                    return $p['id'];
                }, $c));
                $return = ! in_array($key, $ids);
                $ids[]  = $key;

                return $return;
            });

            return $ais;
        }, $age_groups, array_keys($age_groups));

        foreach($composers as $key => &$composer){
            $age_group = array_shift($age_groups);
            list($age_from, $age_to) = explode('_', $key);
            foreach($age_group as $additional_insurance){
                $new_product                         = $base_product;
                $new_product['additional_insurance'] = array_values($additional_insurance);
                $new_product['age_from']             = intval($age_from);
                $new_product['age_to']               = intval($age_to);

                if( ! isset($composer['base'])){
                    $composer = array_merge([
                        'provider_id'  => 202901,
                        'age_from'     => $new_product['age_from'],
                        'age_to'       => $new_product['age_to'],
                        'collectivity' => $new_product['_collectivity'],
                    ], $new_product['composer'], $composer);
                }
                unset($new_product['composer']);

                $products[] = $new_product;
            }
        }

        return [$products, array_values($composers)];
    }

    /**
     * Gropus a set of additional insurances by pre-calculated age-range
     * And splits some products if needed
     * ["18_27" => [{},{},{}], "27_65" => [{},{},{}]]
     *
     * @param $p
     * @param $borders
     *
     * @return mixed
     */
    public static function groupByAgeRanges($p, $borders)
    {
        return array_reduce($p, function ($carry, $ai) use ($borders) {
            while($borders[$ai['age_from']] !== $ai['age_to']){
                $next_age     = $ai['age_to'];
                $ai['age_to'] = $borders[$ai['age_from']];

                $carry[$ai['age_from'] . '_' . $ai['age_to']][$ai['id']] = $ai;

                $ai['age_from'] = $borders[$ai['age_from']];
                $ai['age_to']   = $next_age;
            };
            $carry[$ai['age_from'] . '_' . $ai['age_to']][$ai['id']] = $ai;

            return $carry;
        }, []);
    }


    /**
     * This function glues additional insurance based on age borders and same price
     * [{18-65, €5},{65-99, €5}] --> [{18-99, €5}]
     * This reduces the number of products from 200 to ~90.
     *
     * @param $p
     *
     * @return array
     */
    public static function glueAdditionalProducts($p)
    {
        $x = count($p) - 1;
        for($i = 0; $i < $x; $i ++){
            if(true and ($p[$i]['age_to'] === $p[$i + 1]['age_from']) and ($p[$i]['price'] === $p[$i + 1]['price']) and ($p[$i]['id'] === $p[$i + 1]['id'])){
                $p[$i + 1]['age_from'] = $p[$i]['age_from'];
                unset($p[$i]);
            }
        }

        return array_values($p);
    }

    /**
     * Calculates the age borders for the given set of additional insurances
     * [{18-27}, {18-65}, {65-99}] becomes assotiative array [18=>27, 27=>65, 65=>99]
     *
     * @param $ai array Additional insurances
     *
     * @return array
     */
    public static function getAgeBorders($ai)
    {
        $ages = array_values(array_unique(array_reduce($ai, function ($carry, $ai) {
            return array_merge($carry, [$ai['age_from'], $ai['age_to']]);
        }, [])));
        sort($ages);

        $borders = array_reduce($ages, function ($carry, $age) {
            if( ! empty($carry)){
                $last         = array_pop($carry);
                $carry[$last] = $age;
            }
            $carry[] = $age;

            return $carry;
        }, []);

        return array_slice($borders, 0, - 1, true);
    }

    public static function applyCoveragesBasedOnPakketIds(&$product, ImportIAKCommand $import)
    {
        $ids = array_reduce($product['additional_insurance'], function ($ids, $additional_insurance) {
            $ids[] = $additional_insurance['id'];

            if(isset($additional_insurance['additional_insurance'])){
                $ids = array_reduce($additional_insurance['additional_insurance'], function ($ids, $subproduct_group) {
                    do{
                        $subproduct = array_pop($subproduct_group);
                    }while(preg_match('/ uit$/i', $subproduct['title']));
                    $ids[] = $subproduct['id'];

                    return $ids;
                }, $ids);
            }

            return $ids;
        }, [0]);

        sort($ids);

        $ids_id = implode(',', $ids);

        if( ! ($full_tree = MongoHelper::getCollection('treez')->findOne(['_id' => $ids_id]))){

            $treez = iterator_to_array(MongoHelper::getCollection('treez')->find(['_id' => ['$in' => $ids]], ['_id' => 0]));

            $tree = array_reduce($treez, function ($product_coverages, $tree) {
                return array_replace_recursive($product_coverages, $tree);
            }, []);

            $full_tree = self::mergeCoverages($tree);

            $full_tree = self::sortCoverages($full_tree);

            $full_tree['_id'] = $ids_id;

            MongoHelper::getCollection('treez')->insert($full_tree);
        }else{
            $a = 1;
        }


        $product['coverage'] = $full_tree;
    }

    /**
     * @param $dataset
     *
     * @return array
     */
    public static function buildCoverageTree($dataset)
    {
        $tree = [];
        foreach(array_keys($dataset) as $id){
            $the_coverage = &$dataset[$id];
            if(is_null($the_coverage['parent_type_id'])){
                $tree[self::slug($the_coverage['name'])] = &$the_coverage;
            }else{
                $dataset[$the_coverage['parent_type_id']][self::slug($the_coverage['name'])] = &$the_coverage;
            }

            unset($the_coverage['type_id']);
            unset($the_coverage['variable']);
            unset($the_coverage['var']);
            unset($the_coverage['parent_type_id']);
        }

        return $tree;
    }

    /**
     * @param $name
     *
     * @return string
     */
    public static function slug($name)
    {
        $name = str_replace(['è', 'é', 'ê', 'ë', 'ē', 'ė', 'ę'], 'e', $name);
        $name = str_replace('ö', 'o', $name);

        return trim(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', preg_replace('/[?\.\s\,\-)\(\/:]+/mi', '_', mb_strtolower($name))), '_');
    }

    const GROUP_ADD_NULL = true;
    const GROUP_VERBOSE_KEY = true;

    private static function groupAdditionalInsurancesByType($ais, $verbouseKey = false, $addNull = false)
    {
        static $reverse;
        if( ! isset($reverse)){
            $reverse = array_flip(self::SUB);
        }

        $r = $verbouseKey ? $reverse : false;

        return array_reduce($ais, function ($carry, $ai) use ($addNull, $r) {
            $key = $r ? $r[$ai['type']] : $ai['type'];
            if( ! isset($carry[$key]) and $addNull){
                $carry[$key][] = null;
            }
            $carry[$key][] = $ai;

            return $carry;
        }, []);
    }

    private static function buildComposer($ais)
    {
        $composer = array_map(function ($ai) {
            return array_values(array_map(function ($a) {
                foreach(
                    [
                        'type',
                        'own_risk',
                        'price_yearly',
                        'discount_price_yearly',
                        'discount_own_risk'
                    ] as $key
                ){
                    unset($a[$key]);
                }

                return $a;
            }, array_filter($ai)));
        }, $ais);

        if(isset($composer['combi'])){
            $composer['aanvullend'] = array_merge($composer['aanvullend'], $composer['combi']);
            unset($composer['combi']);
        }

        return $composer;
    }

    public static function getBasicCoverages()
    {
        static $base;
        if( ! isset($base)){
            $base = json_decode(file_get_contents(base_path('base.json')), true);
        }

        return $base;
    }

    public static function mergeCoverages($coverageTree)
    {
        return array_replace_recursive(self::getBasicCoverages(), $coverageTree);
    }

    public static function getNotIDS($collectivity, $company_id = null)
    {
        static $not_ids = [];
        if( ! isset($not_ids[$collectivity])){

            $ids = array_get(MongoHelper::getCollection('verzekeraars', MongoHelper::DB_PROD)->aggregate([
                ['$match' => ['COLLECTIEF_NR' => $collectivity]],
                ['$group' => ['_id' => '$INSURANCE_PROVIDER_ID', 'ids' => ['$addToSet' => '$PRODUCT_ID']]]
            ]), 'result', []);

            $not_ids[$collectivity] = array_reduce($ids, function ($not_ids, $ids) {
                return $not_ids + [$ids['_id'] => array_values(array_diff(self::IAK_IDS[$ids['_id']], $ids['ids']))];
            }, []);
        }

        return is_null($company_id) ? call_user_func_array('array_merge', $not_ids[$collectivity]) : array_get($not_ids[$collectivity], $company_id, []);
    }

    public static function getBaseIDS($collectivity, $company_id)
    {
        static $base_ids = [];
        if( ! isset($base_ids[$collectivity])){

            $base_ids = array_get(MongoHelper::getCollection('verzekeraars', MongoHelper::DB_PROD)->aggregate([
                ['$match' => ['COLLECTIEF_NR' => $collectivity, 'PRODUCT_TYPE_ID' => ['$in' => IAKHelper::BASIC]]],
                ['$group' => ['_id' => '$INSURANCE_PROVIDER_ID', 'ids' => ['$addToSet' => '$PRODUCT_ID']]]
            ]), 'result', []);

            $base_ids[$collectivity] = array_reduce($base_ids, function ($base_ids, $ids) {
                return $base_ids + [$ids['_id'] => $ids['ids']];
            }, []);
        }

        return array_get($base_ids[$collectivity], $company_id, []);
    }

    public static function getSummariesOutOfTree($tree)
    {
        $summ = [];
        foreach($tree as $field => $el){
            if(is_array($el)){
                list($no_summary, $summary) = self::getSummariesOutOfTree($el);
                $tree[$field] = $no_summary;
                $summ[$field] = $summary;
            }elseif($field == 'add_summary'){
                unset($tree[$field]);
                $summ[$field] = $el;
            }
        }

        return [$tree, $summ];
    }

    public static function glueProductsWithSameAdditionalInsurancesAndPrices(array $products)
    {
        return array_values(array_reduce($products, function ($combined_products, $product) {
            $id = array_map(function ($ai) {
                return $ai['id'] . '_' . $ai['price'];
            }, $product['additional_insurance']);

            sort($id);

            $id = implode(',', $id);

            if(array_key_exists($id, $combined_products)){
                $combined_products[$id]['age_to'] = $product['age_to'];
            }else{
                $combined_products[$id] = $product;
            }

            return $combined_products;
        }, []));
    }

    /**
     * @param bool $do_update
     *
     * @return array
     */
    public static function getIAKZorgwebCollectivityGroups($do_update = false)
    {
        $collectivity_groups = MongoHelper::getCollection('verzekeraars')->aggregate([
            //['$match' => ['COLLECTIEF_NR' => 12281]],
            [
                '$group' => [
                    '_id'      => '$COLLECTIEF_NR',
                    'products' => [
                        '$push' => [
                            'id'       => '$PRODUCT_ID',
                            'discount' => '$KORTING',
                            'offer'    => '$COLLECTIEF_AANBOD',
                            'deal'     => '$DEAL',
                        ]
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id'                  => '$products',
                    'collectivities'       => ['$push' => '$_id'],
                    'collectivities_count' => ['$sum' => 1]
                ]
            ],
            ['$sort' => ['collectivities_count' => - 1]]
        ])['result'];

        $collectivity_groups = array_map(function ($group) {

            $products = [];
            foreach($group['_id'] as $product){
                $product['discount'] = floatval(str_replace(['%', ','], ['', '.'], $product['discount']));

                if(isset(self::$iak2017toiak2018productIds[$product['id']])){
                    $product['id'] = self::$iak2017toiak2018productIds[$product['id']];
                }

                $products[$product['id']] = $product;
            }

            ksort($products);

            return [
                'products' => $products,
                'ids'      => $group['collectivities'],
            ];
        }, $collectivity_groups);

        if($do_update){
            // Set all to the 'we have no discounts' collectivity group 133700
            DB::connection('mysql_product')->table('collectivity_healthcare2018')->update([ResourceInterface::COLLECTIVITY_GROUP_ID_IAK => Healthcare::PREMIUM_GROUP_ID_IAK_COLLECTIVITY_ZORGWEB_START]);
        }

        // Update collectivity group ids if they have discounts
        foreach($collectivity_groups as $i => &$group){
            $iak_group_id = Healthcare::PREMIUM_GROUP_ID_IAK_COLLECTIVITY_ZORGWEB_START + $i + 1;

            if($do_update){
                foreach($group['ids'] as $id){
                    DB::connection('mysql_product')->table('collectivity_healthcare2018')->where('__id', $id)->update([ResourceInterface::COLLECTIVITY_GROUP_ID_IAK => $iak_group_id]);
                }
            }

            $group['group_id'] = $iak_group_id;
        }

        $without_discount = DB::connection('mysql_product')->table('collectivity_healthcare2018')->select('__id')->where(ResourceInterface::COLLECTIVITY_GROUP_ID_IAK, Healthcare::PREMIUM_GROUP_ID_IAK_COLLECTIVITY_ZORGWEB_START)->get();

        // The 'no discounts' group
        $collectivity_groups[] = [
            'products' => [],
            'ids'      => array_map(function ($d) {
                return (int) $d->__id;
            }, $without_discount),
            'group_id' => Healthcare::PREMIUM_GROUP_ID_IAK_COLLECTIVITY_ZORGWEB_START,
        ];

        return $collectivity_groups;
    }

    public static function getIAKZorgwebProviderIDs()
    {
        return MongoHelper::getCollection('verzekeraars')->distinct('INSURANCE_PRODIVDER_ID');
    }

    private static function sanitizeCoverages($input)
    {
        if(array_key_exists('gezamenlijk_maximum', $input) and array_key_exists('max_bedrag_p_jr', $input) and $input['max_bedrag_p_jr'] == $input['gezamenlijk_maximum']){
            unset($input['max_bedrag_p_jr']);
        }

        foreach($input as $key => $value){
            if(is_array($value)){
                $input[$key] = self::sanitizeCoverages($input[$key]);
            }
        }

        return $input;
    }

    public static function sortCoverages($full_product_coverages)
    {
        uasort($full_product_coverages, function ($a, $b) {
            $a = intval(is_array($a));
            $b = intval(is_array($b));
            if($a == $b){
                return 0;
            }

            return ($a < $b) ? - 1 : 1;
        });

        if(array_key_exists('description', $full_product_coverages) and count($full_product_coverages) > 1){
            $tmp = $full_product_coverages['description'];
            unset($full_product_coverages['description']);
            $full_product_coverages = ['description' => $tmp] + $full_product_coverages;
        }

        foreach($full_product_coverages as &$value){
            if(is_array($value)){
                $value = self::sortCoverages($value);
            }
        }

        return $full_product_coverages;
    }

    public static function getCompanyRating($company_id)
    {
        if(array_key_exists($company_id, self::COMPANY_RATINGS)){
            return self::COMPANY_RATINGS[$company_id];
        }

        return null;
    }


    public static function fillParams(&$params, $doc, $toppings, $birthdate, $collectivityId, $person = 'main')
    {
        if(array_get($doc, 'company.id') != self::COMPANY_ID){
            return false;
        }

        $packages[] = array_get($doc, 'base_insurance.id');
        foreach($doc['additional_insurance'] as $addId => $additionalInsurance){
            if(isset($additionalInsurance['id'])){
                $packages[] = $additionalInsurance['id'];
            }
        }

        $birthdateKey          = ($person == 'main') ? ResourceInterface::BIRTHDATE : (ResourceInterface::BIRTHDATE . '_' . $person);
        $params[$birthdateKey] = date('Y-m-d', strtotime($birthdate));

        $packagesKey          = ($person == 'main') ? ResourceInterface::PACKAGES : (ResourceInterface::PACKAGES . '_' . $person);
        $params[$packagesKey] = array_unique(array_merge($packages, $toppings));

        if($person == 'main'){
            $params[ResourceInterface::COLLECTIVITY_ID] = $collectivityId;
        }

        return true;
    }

    public static function fillParams2018(&$params, $toppings, $docParams, $collectivityId, $person = 'main')
    {
        if(empty($docParams)){
            return false;
        }
        $insurnanceDoc = Healthcare2018Helper::getProductStructure($docParams[ResourceInterface::PRODUCT_ID], $docParams[ResourceInterface::OWN_RISK], $docParams[ResourceInterface::BIRTHDATE], self::USER_ID, null, $collectivityId);

        if(array_get($insurnanceDoc, 'total_product.provider_id') != self::COMPANY_ID){
            return false;
        }


        //IAK own risk map, WTF???
        $ownRiskMap = [
            'SVR002R' => [
                385 => 1,
                485 => 2,
                585 => 6,
                685 => 4,
                785 => 5,
                885 => 3
            ],
            'SV4R002R' => [
                385 => 1,
                485 => 2,
                585 => 6,
                685 => 4,
                785 => 5,
                885 => 3
            ],
            'SV6R002N' => [
                385 => 1,
                485 => 2,
                585 => 3,
                685 => 4,
                785 => 5,
                885 => 6
            ],
            'SV8R002N' => [
                385 => 1,
                485 => 2,
                585 => 3,
                685 => 4,
                785 => 5,
                885 => 6
            ],
        ];


        //SV8R002N
        $packages = [];

        foreach($insurnanceDoc['structure'] as $package){

            if(isset($package['product_id'])){

                if($package['type'] == 'base'){
                    $packages[] = $package['product_id'] . $ownRiskMap[$package['product_id']][$docParams[ResourceInterface::OWN_RISK]];
                }else{
                    $packages[] = $package['product_id'];
                }

            }
        }

        $mainPerson = $person == 'main';
        //the main person was something else, we need to move shit.
        if( ! $mainPerson && ! isset($params[ResourceInterface::COLLECTIVITY_ID])){
            $mainPerson = true;
        }


        $birthdateKey          = $mainPerson ? ResourceInterface::BIRTHDATE : (ResourceInterface::BIRTHDATE . '_' . $person);
        $params[$birthdateKey] = date('Y-m-d', strtotime($docParams[ResourceInterface::BIRTHDATE]));

        $packagesKey          = ($mainPerson) ? ResourceInterface::PACKAGES : (ResourceInterface::PACKAGES . '_' . $person);
        $params[$packagesKey] = array_unique(array_merge($packages, $toppings));

        if($mainPerson){
            $params[ResourceInterface::COLLECTIVITY_ID] = $collectivityId;
        }

        return true;
    }


    public static function processIak2018(Click $click, $sessionData, $doc, $options, $productType)
    {


        /*
         * main insurer
         */
        $person      = 0;
        $shared_page = '_shared_';
        $cart_key    = 'cart.healthcare2018';
        $params      = [];

        $page           = array_get($sessionData, $person . '.' . $shared_page);
        $cart           = array_get($page, $cart_key);
        $collectivityId = array_get($cart, ResourceInterface::COLLECTIVITY_ID, '1000');

        $toppings = [];


        $nearShoringPersonMap = [
            'applicant'         => 'main',
            'applicant_partner' => 'partner',
            'child0'            => 'child_1',
            'child1'            => 'child_2',
            'child2'            => 'child_3',
            'child3'            => 'child_4',
            'child4'            => 'child_5',
            'child5'            => 'child_6',
            'child6'            => 'child_7',
            'child7'            => 'child_8',
            'child8'            => 'child_9',
            'child9'            => 'child_10',
        ];

        // for all persons have same coverage wishes
        foreach(Healthcare2018Helper::PERSONS as $person_key){
            $docParams = array_get($cart, $person_key, []);
            self::fillParams2018($params, $toppings, $docParams, $collectivityId, $nearShoringPersonMap[$person_key]);
        }

        //dd($params);
        /**
         * if no params are set, do nothing.
         */
        if( ! count($params)){
            cw('no params');
            return;
        }
        if (!Config::get('IAK2018_SALE_CREATED')) {
            Config::set('IAK2018_SALE_INVOKE_CREATE', true);
        }

        /** @var Iak $iakResource */
        try{
            $iakResource = App::make('resource.iak');
            $res         = $iakResource->insurance($params, 'iak/insurance');
            $click->link = Config::get('resource_nearshoring.settings.url');

            //if iak id is found
            if(isset($res['result']['iak_id'])){
                $click->link .= $res['result']['iak_id'];
            }
        }catch(Exception $e){
            Log::warning('Error in IAK webservice! ' . $e->getMessage());
            Log::warning($params);
        }

    }


    public static function processIak(Click $click, $sessionData, $doc, $options, $productType)
    {


        $ids = explode(',', array_get($sessionData, '0.f.selected_id'));


        /*
         * main insurer
         */
        $params         = [];
        $collectivityId = array_get($sessionData, '0.f.collectivity', '1000');
        $mainToppings   = array_has($sessionData, '0.f.topping') ? explode(',', array_get($sessionData, '0.f.topping')) : [];
        $birthdate      = array_get($sessionData, '0.f.aanvrager.geboortedatum', '1977-11-09');

        /**
         * This normally only happens when the main insurer is not IAK, but for safety we reload
         */
        if($ids[0] != $doc['__id']){
            $doc = self::getMainDoc($sessionData, $ids[0], $options, $productType);
        }

        $mainIsIak = self::fillParams($params, $doc, $mainToppings, $birthdate, $collectivityId);

        $docIndex = 1;

        $processedKids   = 0;
        $childInsurer    = 0;
        $childInsurerDoc = $doc;
        $childToppings   = $mainToppings;


        //situation: Partner on same polis
        if(array_has($sessionData, '0.f.partner.geboortedatum') && isset($ids[$docIndex])){
            $birthdate = date('Y-m-d', strtotime(array_get($sessionData, '0.f.partner.geboortedatum')));
            //toppings the same as main surere!
            $partnerDoc = self::getMainDoc($sessionData, $ids[$docIndex], $options, $productType);
            self::fillParams($params, $partnerDoc, $mainToppings, $birthdate, $collectivityId, 'partner');
            $docIndex ++;
        }
        //other adults
        for($person = 1; $person < 5; $person ++){
            if(array_has($sessionData, $person . '.f.aanvrager.geboortedatum')){
                //we have a winner
                $isPartner = (str_contains(array_get($sessionData, $person . '.label', 'nopartner'), 'Partner'));


                //if no partner we have extra child
                if( ! $isPartner){
                    $processedKids ++;
                    $personkey = 'child_' . $processedKids;
                }else{
                    //if partner, check if kids are on policy
                    $personkey = $mainIsIak ? 'partner' : 'main';
                }
                $toppings  = array_has($sessionData, $person . '.f.topping') ? (explode(',', array_get($sessionData, $person . '.f.topping'))) : [];
                $birthdate = date('Y-m-d', strtotime(array_get($sessionData, $person . '.f.aanvrager.geboortedatum')));

                $nextAdultDoc = self::getMainDoc($sessionData, array_get($sessionData, $person . '.f.selected_id'), $options, $productType);

                //kids insured on partner
                if($isPartner && str_contains(array_get($sessionData, $person . '.label', 'nopartner'), 'kinderen')){
                    $childInsurer = $person;

                    $childInsurerDoc = $nextAdultDoc;
                    $childToppings   = $toppings;
                }

                self::fillParams($params, $nextAdultDoc, $toppings, $birthdate, $collectivityId, $personkey);
            }
        }

        /**
         * Children on right policy
         */
        for($child = 0; $child < 5; $child ++){
            $childBirthdateKey = $childInsurer . '.f.kinderen.' . $child . '.geboortedatum';
            if(array_has($sessionData, $childBirthdateKey)){
                $processedKids ++;
                $birthdate = date('Y-m-d', strtotime(array_get($sessionData, $childBirthdateKey)));
                if(self::isAdult($birthdate) && isset($ids[$docIndex])){
                    //use next id
                    $childDoc = self::getMainDoc($sessionData, $ids[$docIndex], $options, $productType);
                    self::fillParams($params, $childDoc, $childToppings, $birthdate, $collectivityId, ('child_' . $processedKids));
                    $docIndex ++;

                }else{
                    //minor child, on polis of aanvrager in this case
                    self::fillParams($params, $childInsurerDoc, $childToppings, $birthdate, $collectivityId, ('child_' . $processedKids));
                }
            }
        }

        /**
         * if no params are set, do nothing.
         */
        if( ! count($params)){
            return;
        }


        /** @var Iak $iakResource */
        try{
            $iakResource = App::make('resource.iak');
            $res         = $iakResource->insurance($params, 'iak/insurance');
            $click->link = Config::get('resource_nearshoring.settings.url');


            //if iak id is found
            if(isset($res['result']['iak_id'])){
                $click->link .= $res['result']['iak_id'];
            }
        }catch(Exception $e){
            Log::warning('Error in IAK webservice! ' . $e->getMessage());
            Log::warning($params);
        }
    }


    /**
     * @param $sessionData
     * @param $docId
     * @param $options
     * @param $productType
     *
     * @return array|\Komparu\Document\Contract\Response|null
     */
    public static function getDocHC2018($docId, $options, $user)
    {
        $resource2Options = isset($options['conditions']) ? $options['conditions'] : $options;
        //add logic for daisycon user
        if($user->getRight('daisycon_widget')){
            $resource2Options[ResourceInterface::DAISYCON] = 1;
        }
        try{
            if(starts_with($docId, 'A')){
                $doc = DocumentHelper::show('product', 'healthcare2018', $docId);
                if( ! $doc){
                    Log::warning('Could not find this HC2018 doc ID! ' . $docId);
                    return [];
                }
                $providerId = $doc->provider_id;
                $companies  = ResourceHelper::callResource2('company.healthcare2018', $resource2Options + ['resource_id' => $providerId]);
                if( ! count($companies)){
                    return [];
                }
                $doc          = head($companies);
                $doc['title'] = $doc['name'];
            }else{
                cws('documenthelper_show');
                $doc = ResourceHelper::callResource2('product.healthcare2018', $resource2Options, App\Listeners\Resources2\RestListener::ACTION_SHOW, $docId);
                cwe('documenthelper_show');
            }
        }catch(Exception $e){
            return [];
        }
        return $doc;
    }

    /**
     * @param $sessionData
     * @param $docId
     * @param $options
     * @param $productType
     *
     * @return array|\Komparu\Document\Contract\Response|null
     */
    public static function getMainDoc($sessionData, $docId, $options, $productType)
    {
        if(array_has($sessionData, '0.f.collectivity')){
            $options['filters']            = ['_collectivity' => array_get($sessionData, '0.f.collectivity'), '__id' => $docId];
            $options['baseinsurancegroup'] = true;
            $doc                           = DocumentHelper::get('product', $productType->name, $options, true);
            if($doc != null){
                $docArr = DocumentHelper::get('product', $productType->name, $options, true)->toArray();
                $doc    = isset($docArr['documents'], $docArr['documents'][0]) ? $docArr['documents'][0] : null;
            }
        }else{
            try{
                $doc = DocumentHelper::show('product', $productType->name, $docId, $options, true);
            }catch(DocumentNotFound $e){
                return [];
            }
            if( ! $doc){
                return [];
            }
            $doc = $doc->toArray();
        }

        return $doc;
    }

    public static function isAdult($date)
    {
        /**
         * Create first of year
         */
        if( ! self::$firstOfYear){
            $curMonth = (int) date('n');
            $curYear  = (int) date('Y');
            if($curMonth >= 11){
                $firstDayNextMonth = mktime(0, 0, 0, 0, 0, $curYear + 1);
            }else{
                $firstDayNextMonth = mktime(0, 0, 0, $curMonth + 1, 1);
            }
            self::$firstOfYear = new DateTime(date('Y-m-d', $firstDayNextMonth));
        }


        $format    = 'Y-m-d';
        $birthDate = DateTime::createFromFormat($format, $date);
        $interval  = self::$firstOfYear->diff($birthDate);
        $years     = $interval->y;

        return ($years >= 18);
    }

    // this is to get the file producten
    // and get the relation between pakket id and its actual pakket
    // because the actual product is identified by its pakket
    // and IDs only differ between collectivities
    // e.g.
    // PKT1R001 = "PKT1"
    // PKT1R002 = "PKT1"
    // PKT1R003 = "PKT1"
    // only one set of collectivities will have PKT1R001 id and it will never intersect with others
    public static function loadPakketIDRelations()
    {
        $pakketten = iterator_to_array(MongoHelper::getCollection('producten')->find());

        return array_reduce($pakketten, function ($pakketIDs, $pakket) {
            $pakketIDs[$pakket['PAKKETID']] = preg_match('/^(SV.*?)[\d]$/', $pakket['PAKKETID'], $matches) ? $matches[1] : $pakket['PAKKET'];

            return $pakketIDs;
        }, []);
    }

    /**
     * @param $title
     *
     * @return string
     */
    public static function productType($title)
    {
        preg_match('/(buiten|wereld|europa)|(fysio)|(alternati)|(calamiteit)|(gezinsplanning)|(therapie)|(ortho)|(senior)|(geboorte)|(optiek)/mi', $title, $matches);
        if( ! empty($matches[1])){
            return 'buitenland';
        }elseif( ! empty($matches[2])){
            return 'fysio';
        }elseif( ! empty($matches[3])){
            return 'alternatief';
        }elseif( ! empty($matches[4])){
            return 'calamiteiten';
        }elseif( ! empty($matches[5])){
            return 'gezinsplanning';
        }elseif( ! empty($matches[6])){
            return 'therapie';
        }elseif( ! empty($matches[7])){
            return 'ortho';
        }elseif( ! empty($matches[8])){
            return 'senior';
        }elseif( ! empty($matches[9])){
            return 'geboorte';
        }elseif( ! empty($matches[10])){
            return 'optiek';
        }else{
            return 'aanvullend';
        }
    }

    public static function generateIAKProductId($productCombo, $commaSeparated = false)
    {
        $ids = array_merge([$productCombo['base']], array_values(array_get($productCombo, 'additional', [])), array_values(array_get($productCombo, 'toppings', [])));

        return implode($commaSeparated ? ',' : '', $ids);
    }
}