<?php


namespace App\Resources\Zanox\Methods\Shoes;


use App\Helpers\ResourceFilterHelper;
use Komparu\Value\ValueInterface;


class DefaultProvider
{
    protected $colls = [];
    protected $data;
    protected $debugButton = false;
    protected $language = 'nl';

    public static $basicColorDefine = ['nl'  => ['Zwart', 'Wit', 'Beige', 'Roze', 'Rood', 'Blauw', 'Paars', 'Groen', 'Grijs', 'Geel', 'Oranje', 'Bruin', 'Zilver', 'Goud', 'Brons', 'Kleurrijk'],
                                       'en'  => ['Black', 'White', 'Beige', 'Pink', 'Red', 'Blue', 'Violet', 'Green', 'Grey', 'Yellow', 'Orange', 'Brown', 'Silver', 'Gold', 'Brass', 'Colorful'],
                                       'ger' => ['Schwarz', 'Weiss', 'Beige', 'Rosa', 'Rot', 'Blau', 'Lila', 'Grün', 'Grau', 'Gelb', 'Orange', 'Braun', 'Silber', 'Gold', 'Bronze', 'Bunt']];

    public function __construct()
    {
    }

    public function set_data($data)
    {
        $xml = simplexml_load_string($data->asXML(), 'SimpleXMLElement', LIBXML_NOCDATA);
        $array = json_decode(json_encode($xml), true);

        //$pattern = '/^((?:(?![hH]andschoen).)*)([sS]choen)*$/';
        $pattern1 = '/([sS]choen)|([sS]hoes)/'; //[sS][c]{0,1}hoe[ns] is correct as well
        $pattern2 = '/[hH]andschoen/';

        if (preg_match($pattern1, $array['category_path'])) {
            if (!preg_match($pattern2, $array['category_path'])) {
                $this->data = $array;
            }
        }
    }


    public function get_data()
    {
        return $this->data;
    }

    public function process_justfilterout()
    {
    }

    public function process_discount()
    {
        if (array_key_exists('discount', $this->data)) {
            if (is_array($this->data['discount']))
                $this->data['discount'] = null;
        }
    }

    public function process_size_stock()
    {
        // if($this->debugButton) print_r("size_stock(start|");
        if (!array_key_exists("size_stock", $this->data)) {
            $this->data["size_stock"] = [];
            return;
        }

        if (empty($this->data["size_stock"])) {
            $this->data["size_stock"] = [];
            return;
        }

        if (is_array($this->data["size_stock"])) {
            return;
        }

        if (!preg_match('/(,)/', $this->data["size_stock"])) {
            $this->data["size_stock"] = [$this->data["size_stock"]];
            return;
        }

        $this->data["size_stock"] = array_values(explode(',', $this->data["size_stock"]));
        //if($this->debugButton) print_r("end)");
    }

    public function process_stock()
    {
        $TermDefine = ['nl'  => ['nu beschikbaar.', 'niet op voorraad', 'Geen Informatie'],
                       'en'  => ['immediately available.', 'not immediately available.', 'No Information'],
                       'ger' => ['sofort verfügbar.', 'nicht sofort verfügbar.', 'aktuell keine Information']];

        $stockTermDefine = ['sofort verfügbar.' => '0', //nu beschikbaar.
                            '1'                 => '0',  //nu beschikbaar.
                            '0'                 => '1'  //niet op voorraad
        ];

        if (empty($this->data['stock'])) {
            $this->data['stock'] = $TermDefine[$this->language][2];
            return;
        }

        if (!array_key_exists($this->data['stock'], $stockTermDefine)) {
            return;
        }

        $this->data['stock'] = $TermDefine[$this->language][$stockTermDefine[$this->data['stock']]];
    }

    public function process_size()
    {
        $pregRewriteRules = ['[\|]' => ',',
                             '[\+]' => '.5',
                             '[/]'  => ','];

        $sizeString = $this->data["size"];

        if (is_array($sizeString)) {
            $this->data["size"] = [];
            return;
        }

        $sizeString = ResourceFilterHelper::multiPregReplace($sizeString, $pregRewriteRules);
        $sizeString = str_replace(" ", "", $sizeString);
        $sizeString = str_replace(",,", ",", $sizeString);

        $pattern1 = '/[1-5][0-9]-[1-5][0-9]/';
        if (preg_match($pattern1, $sizeString)) {
            $sizeString = preg_replace('/[-]/', ',', $sizeString);
        }

        $this->data["size"] = array_values(explode(",", $sizeString));
    }

    public function process_price_shipping()
    {
        if (array_key_exists('price_shipping', $this->data)) {
            if (is_array($this->data['price_shipping']))
                $this->data['price_shipping'] = null;
        }
    }

    public function process_matrial()
    {
        $TermDefine = ['nl'  => ['Geen Informatie'],
                       'en'  => ['No Information'],
                       'ger' => ['aktuell keine Information']];

        if (empty($this->data['material'])) {
            $this->data['material'] = $TermDefine[$this->language][0];
            return;
        }

        if (is_array($this->data['material'])) {
            $this->data['material'] = implode(',', $this->data['material']);
        }

        $pregReplaceArray = ['/^leer/' => $TermDefine[$this->language][0]];
        $this->data['material'] = ResourceFilterHelper::multiPregReplace($this->data['material'], $pregReplaceArray);
    }

    public function process_category_path()
    {
        //if($this->debugButton)  print_r("category_path(start|");
        $catString = strtolower($this->data["category_path"]);
        $pregRewriteRules = ['[>]'    => '-',
                             '[\\\\]' => '-',
                             '[/]'    => '-',
        ];

        foreach ($pregRewriteRules as $pattern => $repacement) {
            if (preg_match($pattern, $catString)) {
                $catString = preg_replace($pattern, $repacement, $catString);
                break;
            }
        }

        $this->data["tags"] = array_values(array_filter(explode(' - ', $catString)));

        $nullFieldArray = ['category_path', 'category', 'subcategory', 'thirdcategory', 'fourth_category'];
        $this->setNull($nullFieldArray);
        // if($this->debugButton)  print_r("end)");
    }

    public function process_title_origin()
    {
        $this->data['title_origin'] = $this->data['title'];
    }

    public function process_title()
    {
        $pregReplaceArray = ['[/]' => '[/]',];
        $vendor = ResourceFilterHelper::multiPregReplace($this->data['vendor'], $pregReplaceArray);
        $pregReplaceArray = ['(' . $vendor . '[\s])' => ''];
        $this->data['title'] = ResourceFilterHelper::multiPregReplace($this->data['title'], $pregReplaceArray);
    }

    public function process_gender()
    {
        $this->data['gender'] = ResourceFilterHelper::getGenderDefinition($this->data['gender']);
    }


    public function process_color()
    {
        $colorString = $this->data["color"];

        if (is_array($colorString))
            $colorString = "";

        $this->data["color_origin"] = $colorString;

        $colorString = strtolower($colorString);
        $colorDefinition = ResourceFilterHelper::getColorDefinition();
        $colorString = empty($colorString) ? 'N/A' : $colorString;


        // The number of the color is deduced out of $basicColorDefine
        $automaticColorDefine = [
            'Marine'     => 5,  /* 5 = Blue*/
            'Navy'       => 5,  /* 5 = Blue*/
            'Wine'       => 4, /* 4 = Red*/
            'Rust'       => 11, /* 11 = Bruin*/
            'bronze'     => 11, /* 11 = Bruin*/
            'khaki'      => 7, /* 7 = Green*/
            'Kaki'       => 7, /* 7 = Green*/
            'mint'       => 7, /* 7 = Green*/
            'taupe'      => 8, /* 8 = Grey*/
            'nude'       => 2, /* 2 = Beige*/
            'peach'      => 10, /* 10 = Orange*/
            'plum'       => 4, /* 4 = Red*/
            'Peanut'     => 11, /* 11 = Bruin*/
            'teal'       => 5,  /* 5 = Blue*/
            'Print'      => 15, /* 15 = Kleurrijk*/
            'tortoise'   => 15, /* 15 = Kleurrijk*/
            'Tan'        => 11, /* 11 = Bruin*/
            'Chocola'    => 11, /* 11 = Bruin*/
            'pewter'     => 12, /* 12 = Silver*/
            'multi'      => 15, /* 15 = Kleurrijk*/
            'mono'       => 15, /* 15 = Kleurrijk*/
            'velvet'     => 15, /* 15 = Kleurrijk*/
            'snake'      => 15, /* 15 = Kleurrijk*/
            'glitter'    => 15, /* 15 = Kleurrijk*/
            'stripe'     => 15, /* 15 = Kleurrijk*/
            'iridescent' => 15, /* 15 = Kleurrijk*/
        ];

        $arrayLangSearch = ['nl', 'en', 'ger'];
        $arrayColorSet = [];

        foreach ($arrayLangSearch as $lang) {
            foreach (self::$basicColorDefine[$lang] as $key => $value) {
                $arrayColorSet[$value] = $key;
            }
        }

        foreach ($automaticColorDefine as $key => $value) {
            if (!array_key_exists($key, $arrayColorSet))
                $arrayColorSet[$key] = $value;
        }

        //look into the specificDefineColor
        foreach ($colorDefinition as $colorKey => $colorVal) {
            if (in_array($colorString, $colorVal)) {
                $this->data["color"] = $colorKey;
                return;
            }
        }

        //look if you can find something else color
        foreach ($arrayColorSet as $colorSetKey => $arraySingleColorSet) {
            if (preg_match('/' . strtolower($colorSetKey) . '/', $colorString)) {
                $this->data["color"] = self::$basicColorDefine[$this->language][$arraySingleColorSet];
                return;
            }
        }


//        if (isset($colorDefinition[$colorString])) {
//            $this->data["color"] = $colorDefinition[$colorString];
//        } else {
//            foreach ($arrayColorSet as $colorSetKey => $arraySingleColorSet) {
//                if (preg_match('/' . strtolower($colorSetKey) . '/', $colorString)) {
//                    $this->data["color"] = self::$basicColorDefine[$this->language][$arraySingleColorSet];
//                    break;
//                }
//            }
//        }

        //Colors which have no Basic-Color assignment will add to Kleurrijk
        if (!in_array($this->data["color"], self::$basicColorDefine['nl'])) {
            $this->data["color"] = 'Kleurrijk';
            //print_r($this->data["color"].'|');
        }
    }

    public function setNull($nullFieldArray)
    {
        foreach ($this->data["tags"] as $first) {
            foreach ($nullFieldArray as $key) {
                if (isset($this->data[$key])) {
                    if (!is_array($this->data[$key])) {
                        if (preg_match("/" . $first . "/", $this->data[$key])) ;
                        $this->data[$key] = null;
                    } else
                        $this->data[$key] = null;
                }
            }
        }
    }

    public function process_tags()
    {
        $searchPreg = [
            '/^heren$/'          => 'schoenen',
            '/^dames$/'          => 'schoenen',
            '/^kinder[en]*$/'    => 'schoenen',
            '/^meisjes$/'        => 'schoenen',
            '/^jongens$/'        => 'schoenen',
            '/^baby$/'           => 'schoenen',
            '/(heren)(\s)*/'     => '',
            '/(dames)(\s)*/'     => '',
            '/(meisjes)(\s)*/'   => '',
            '/(jongens)(\s)*/'   => '',
            '/(kinderen*)(\s)*/' => '',
            '/(kinder*)(\s)*/'   => '',
            '/(baby)(\s)*/'      => '',
            '/(teen)(\s)*/'      => '',
            '/^sport$/'          => 'sportschoenen'];


        $this->data = ResourceFilterHelper::renameCategory($this->data, $searchPreg);

        if (in_array('', $this->data["tags"]))
            var_dump($this->data["tags"]);
    }

    public function process_main()
    {
    }

    // geen blackberry abbos
    public function is_excluded()
    {
        return false;
    }

}
