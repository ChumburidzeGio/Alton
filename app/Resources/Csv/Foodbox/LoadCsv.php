<?php


namespace App\Resources\Csv\Foodbox;

use App\Resources\BasicCsvRequest;
use App\Helpers\ResourceFilterHelper;

/**
 * Dus, resources laad echt de resource request in, in dit geval gaat ie dan naar de CSV importer
 * Foodbox csv framework
 * User: Roeland Werring
 * Date: 03/09/15
 * Time: 20:12
 */
class LoadCsv extends BasicCsvRequest
{

    protected $headerMap = [];

//the filter execute sequence is very important
    protected $processFields = [
        'ingredients',
        'subcategory',
        'momentOfDelivery',
        'keuken',
        //'boxtype',
        'allergic_info',
        'label',
        'meals_per_week',
        'delivery_costs',
        'resourceid',
        'nr_of_persons',
        'keurmerk',
        'price',
        'active_enabled_clickable'
    ];

    protected $filterMap = [
        'price'              => 'comma_to_dot',
        'category'           => ['doStringToLowercase', 'split_to_array'],
        'subcategory'        => ['doStringToLowercase', 'split_to_array'],
        'ingredients'        => ['doStringToLowercase', 'split_to_array'],
        'allergic_info'      => ['doStringToLowercase', 'split_to_array'],
        'days_of_delivery'   => ['doStringToLowercase', 'normalizeDeliveryDay', 'split_to_array'],
        'moment_of_delivery' => ['doStringToLowercase', 'split_to_array'],
        'delivery_costs'     => ['doStringToLowercase'],
        'keurmerk'           => ['doStringToLowercase', 'split_to_array'],
    ];


    public function __construct()
    {
        parent::__construct(__DIR__);
    }

    public function process_ingredients($input) {
        array_walk($input, function (&$product) {
            $product['ingredients'] = array_map('ucfirst', $product['ingredients']);
        });

        return $input;
    }

    public function process_subcategory($input)
    {
        $arraySearch = [
            'global cooking local sourcing' => 'lokale keuken',
            'locally grown'                 => 'lokale keuken',
            'lokaal'                        => 'lokale keuken',
            'streekgebonden'                => 'lokale keuken',
            'nederlandse bodem'             => 'lokale keuken',
            'internationaal'                => 'internationale keuken'
        ];

        $arrayCatToIngr = [
            'biologisch'                => '100% biologisch',
            'bio maaltijden'            => '100% biologisch',
            'bio producten'             => '100% biologisch',
            'deels organic'             => 'Deels biologisch',
            'bio maaltijden & extra\'s' => '100% biologisch',
            'deels biologisch'          => 'Deels biologisch',
            'detoxkuur'                 => 'Detox',
        ];


        foreach ($input as $foodboxKey => $foodboxValue) {
            array_walk($foodboxValue['subcategory'], 'trim');

            foreach ($foodboxValue['ingredients'] as $key => $value) {
                foreach ($arraySearch as $searchKey => $searchVal) {
                    if ($value == $searchKey) {
                        if (!in_array($searchKey, $foodboxValue['subcategory'])) {
                            array_push($foodboxValue['subcategory'], $searchVal);
                        }
                        unset($foodboxValue['ingredients'][$key]);
                    }
                }
            }
            $input[$foodboxKey]['subcategory'] = $foodboxValue['subcategory'];

            foreach ($arrayCatToIngr as $key => $val) {
                if (in_array($key, $foodboxValue['subcategory'])) {
                    array_push($foodboxValue['ingredients'], $val);
                }
            }
            $input[$foodboxKey]['ingredients'] = array_values(array_unique($foodboxValue['ingredients']));
        }

        return $input;
    }

    public function process_momentOfDelivery($input)
    {
        $patternArray = [
            '/ochtend/' => 'ochtend',
            '/middag/'  => 'middag',
            '/avond/'   => 'avond'
        ];

        foreach ($input as $dataKey => $dataVal) {
            $input[$dataKey]['moment_of_delivery'] = array_values(ResourceFilterHelper::adjustCategory($dataVal['moment_of_delivery'], $patternArray));
        }

        return $input;
    }

    public function process_keuken($input)
    {
        array_walk($input, function (&$product) {
            $product['keuken'] = array_values(array_filter(array_map('ucfirst', array_map('trim', explode(',', $product['Keuken'])))));
            unset($product['Keuken']);
        });

        return $input;
    }

    public function process_delivery_costs($input)
    {
        foreach ($input as $dataKey => $dataVal) {
            $replaceArray              = ['/(bijv. 5,- of )/' => ''];
            $dataVal['delivery_costs'] = ResourceFilterHelper::multiPregReplace($dataVal['delivery_costs'], $replaceArray);
            $input[$dataKey]           = $dataVal;
        }

        return $input;

    }

    public function process_boxtype($input)
    {
        $arrayDefineBox = [
            'fruit'               => ['Fruit'],
            'snack'               => ['Fruit'],
            'Veggie Box'          => ['Vegetarisch', 'Diner'],
            'Veggiebox'           => ['Vegetarisch', 'Diner'],
            'JUIZ~S Box 12'       => ['Sapjes'],
            'JUIZ~S Box 20'       => ['Sapjes'],
            'JUIZ~S Box 30'       => ['Sapjes'],
            'JUIZ~S Box 40'       => ['Sapjes'],
            'JUIZ~S Till Dinner3' => ['Sapjes'],
            'JUIZ~S Till Dinner5' => ['Sapjes'],
            'JUIZ~S Till Dinner6' => ['Sapjes'],
            'JUIZ~S All Day3'     => ['Sapjes'],
            'JUIZ~S All Day5'     => ['Sapjes'],
            'JUIZ~S All Day6'     => ['Sapjes'],
            'veggie'              => ['Vegetarisch', 'Diner'],
        ];

        foreach ($input as $dataKey => $dataVal) {

            $dataVal['boxtype'] = [];
            foreach ($arrayDefineBox as $boxKey => $boxVal) {
                if (preg_match('/' . strtolower($boxKey) . '/', strtolower($dataVal['title']))) {
                    foreach ($boxVal as $val) {
                        $dataVal['boxtype'][] = $val;
                    }
                    break;
                }
            }


            if (empty($dataVal['boxtype'])) {
                $dataVal['boxtype'][] = 'Diner';
            }


            $dataVal['boxtype'] = array_values($dataVal['boxtype']);

            $input[$dataKey] = $dataVal;
        }

        $snackFilter = 0;
        $fruitFilter = 0;
        foreach ($input as $dataKey => $dataVal) {
            if (in_array('Snack', $dataVal['boxtype'])) {
                $snackFilter = 1;
            }

            if (in_array('Fruit', $dataVal['boxtype'])) {
                $fruitFilter = 1;
            }
        }

        $emptyElement        = end($input);
        $emptyElement['url'] = '';

        $emptyElement['company_id']    = 'noname';
        $emptyElement['description']   = '';
        $emptyElement['company_title'] = 'unknown';
        $emptyElement['company_image'] = '';
        $emptyElement['nr_of_persons'] = 1;
        $emptyElement['image']         = '';
        $emptyElement['ingredients']   = [];
        $emptyElement['price']         = '50.00';
        $emptyElement['resourceid']    = 0;
        $emptyElement['allergic_info'] = [];


        if ($snackFilter == 0) {
            $emptyElement['title']   = 'no Snackbox';
            $emptyElement['boxtype'] = ['Snack'];
            array_push($input, $emptyElement);
        }

        if ($fruitFilter == 0) {
            $emptyElement['title']   = 'no Fruitbox';
            $emptyElement['boxtype'] = ['Fruit'];
            array_push($input, $emptyElement);
        }


        return $input;
    }


    public function process_allergic_info($input)
    {


        $patternReplace    = ['/ja/' => 'informatie beschikbaar'];
        $arrayAllergicInfo = ['Glutenvrij', 'Lactosevrij', 'Koolhydraatarm'];

        foreach ($input as $dataKey => $dataVal) {
            $dataVal['allergic_info'] = ResourceFilterHelper::adjustCategory($dataVal['allergic_info'], $patternReplace);
            $dataVal['allergic_info'] = array_map('trim', $dataVal['allergic_info']);

            foreach ($arrayAllergicInfo as $key => $val) {
                if (in_array(strtolower($val), $dataVal['allergic_info'])) {
                    array_push($dataVal['ingredients'], $val);
                }
            }
            $input[$dataKey] = $dataVal;
        }

        return $input;
    }


    public function process_label($input)
    {

        $arrayLabel = [
            '/streekbox/'          => ['//code.komparu.dev/userfiles/aanbieders/eko.png', '//code.komparu.dev/userfiles/aanbieders/fairtrade.png'],
            '/zinnerdinner/'       => ['//code.komparu.dev/userfiles/aanbieders/eko.png', '//code.komparu.dev/userfiles/aanbieders/fairtrade.png'],
            '/beebox/'             => ['//code.komparu.dev/userfiles/aanbieders/eko.png'],
            '/mathijsmaaltijdbox/' => ['//code.komparu.dev/userfiles/aanbieders/marine_stewardship_council.png', '//code.komparu.dev/userfiles/aanbieders/asc.png']
        ];

        foreach ($input as $dataKey => $dataVal) {
            foreach ($arrayLabel as $labelKey => $labelVal) {
                if (preg_match($labelKey, $dataVal['resourceid'])) {
                    $dataVal['label_image'] = '';
                    foreach ($labelVal as $Val) {
                        $dataVal['label_image'] = $Val;
                        break;
                    }
                }
            }
            $input[$dataKey] = $dataVal;
        }

        return $input;
    }

    public function process_meals_per_week($input)
    {
        foreach ($input as $key => $product) {
            $product['meals_per_week_origin'] = $product['meals_per_week'];

            if ($product['meals_per_week'] == '') {
                $product['meals_per_week']        = 1;
                $product['meals_per_week_origin'] = 1;
            } elseif ($product['meals_per_week'] > 5) {
                $product['meals_per_week'] = 5;
            } elseif (preg_match('/[^0-9]/', $product['meals_per_week'])) {
                $product['meals_per_week'] = 1;
            } elseif (is_string($product['meals_per_week'])) {
                $product['meals_per_week'] = (int) $product['meals_per_week'];
            }

            $input[$key] = $product;
        }

        return $input;
    }

    public function process_resourceid($input)
    {
        array_walk($input, function (&$product) {
            $product['resource.id'] = $product['resourceid'];
            unset($product['resourceid']);
        });

        return $input;
    }

    public function process_nr_of_persons($input)
    {
        array_walk($input, function (&$product) {
            if (!is_numeric($product['nr_of_persons'])) {
                preg_match_all('/\d/', $product['nr_of_persons'], $m);
                $product['nr_of_persons'] = max(array_map('intval', $m[0]));
            } elseif (is_string($product['nr_of_persons'])) {
                $product['nr_of_persons'] = (int) $product['nr_of_persons'];
            }
        });

        return $input;
    }

    public function process_keurmerk($input)
    {
        array_walk($input, function (&$product) {
            $product['keurmerk'] = array_map('trim', $product['keurmerk']);
            if ($product['keurmerk'][0] === '') {
                $product['keurmerk'] = [];
            }
        });

        return $input;
    }

    public function process_price($input)
    {
        array_walk($input, function (&$product) {
            $product['price'] = floatval(preg_replace('/[^\d\.]/', '', $product['price']));
        });

        return $input;
    }

    public function process_active_enabled_clickable($input)
    {
        array_walk($input, function (&$product) {
            array_map(function ($field) use (&$product) {
                $product[$field] = true;
            }, ['active', 'enabled', 'clickable']);
        });

        return $input;
    }
}
