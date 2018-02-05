<?php
/**
 * User: Roeland Werring
 * Date: 14/03/15
 * Time: 10:36
 */

namespace App\Helpers;

use Komparu\Value\ValueInterface;

class ResourceFilterHelper
{


    //    static protected $_mobile_color_web = array
    //    (
    //        'Zwart' => '#111',
    //        'Blauw' => '#1164FF',
    //        'Wit' => '#fff',
    //        'Zilver' => '#C0C0C0',
    //        'Geel' => '#F3EE18',
    //        'Groen' => '#52C934',
    //        'Roze' => '#FF99FF',
    //        'Grijs' => '#aaa',
    //        'Goud' => '#FFD24D',
    //        'Paars' => '#C721AE',
    //        'Rood' => '#E60000',
    //        'Bruin' => '#9D2700',
    //        'Oranje' => '#FF9326'
    //    );

    /**
     * Convert cents to euro's (divide by 100)
     * @return string
     */
    public static function filterCentToEuro($value)
    {

        return floatval(number_format($value / 100, 2, '.', ''));
    }

    public static function filterRoundToCent($value)
    {
        return $value;
    }

    public static function filterDivide1000($value)
    {
        return floatval(number_format($value / 1000, 2, '.', ''));
    }

    public static function filterToLowercase($value)
    {

        return strtolower($value);
    }

    public static function filterToUppercase($value)
    {
        return strtoupper($value);
    }

    public static function filterToUppercaseStrip($value)
    {
        return preg_replace("/[^A-Z]/", "", strtoupper($value));
    }


    public static function filterBooleanToInt($value)
    {
        if(is_numeric($value)){
            return $value;
        }

        return ($value == "true") ? 1 : 0;
    }


    public static function filterNumber($value)
    {
        return preg_replace("/[^0-9]/", "", $value);
    }

    public static function filterNumberAddMonthDay($value)
    {
        return preg_replace("/[^0-9]/", "", $value) . '0101';
    }

    public static function filterAlfaNumber($value)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    public static function createCacheKey($params)
    {
        return str_replace('-', '', strtolower(json_encode($params)));
    }

    public static function convertToType($val)
    {
        if(is_array($val)){
            return $val;
        }
        if(is_numeric($val)){
            return $val + 0;
        }

        return $val;
    }

    public static function convertMinusOneToInfinite($value)
    {
        if($value != - 1){
            return $value;
        }

        return ValueInterface::INFINITE;
    }

    public static function convertToDutchBool($boolean)
    {
        if(is_numeric($boolean)){
            return $boolean && $boolean == 1 ? 'ja' : 'nee';
        }

        return $boolean && strtolower($boolean) == 'true' ? 'ja' : 'nee';
    }

    public static function strToBool($string)
    {
        return (($string == 1) || ($string == '1') || ($string == 'true'));
    }

    public static function filterUpperCaseFirst($string)
    {
        return ucfirst(strtolower($string));
    }

    public static function removeWhitespace($string)
    {
        return preg_replace('/\s+/', '', $string);
    }

    public static function filterYearToMonth($price)
    {
        return (float) $price / 12;
    }

    /**
     * Old functions for CI
     *
     * @param $replace
     *
     * @return mixed
     */


    public static function filterExtractFloat($replace)
    {
        return self::regexp_float(self::remove_new_lines(trim(str_replace(',', '.', trim($replace)))));
    }

    public static function regexp_float($string)
    {
        return preg_replace("/[^0-9\.]/", "", $string);
    }

    public static function remove_new_lines($string)
    {
        return trim(preg_replace('/\s\s+/', ' ', $string));
    }

    public static function filterConvertTerm($term)
    {
        if($term == ""){
            return 0;
        }
        if(stripos($term, 'onbeperkt') !== false){
            return ValueInterface::INFINITE;
        }
        if(stripos($term, 'unlimited') !== false){
            return ValueInterface::INFINITE;
        }
        if(stripos($term, 'onb.') !== false){
            return ValueInterface::INFINITE;
        }
        if(stripos($term, 'Oneindig') !== false){
            return ValueInterface::INFINITE;
        }
        if(stripos($term, 'geen') !== false){
            return 0;
        }
        if(stripos($term, 'n.v.t.') !== false){
            return 0;
        }
        // als 'maand' => 1;
        if(stripos($term, 'maand') !== false && stripos($term, 'maanden') === false && stripos($term, 'maand(en)') === false){
            return 1;
        }

        return self::filterNumber($term);
    }


    /**
     * Speed filters
     */

    public static function filterGetSpeed($function = 'get_provider_abbo_speed_down', $provider = 'ben', $data = '0')
    {
        $provider    = (string) strtolower($provider);
        $speed_array = self::$function();
        $speed       = 0;
        if(array_key_exists($provider, $speed_array) === false){
            return $speed;
        }
        foreach($speed_array[$provider] as $k => $v){
            if($data < $k){
                $speed = $v;
                break;
            }
        }

        return $speed;
    }

    public static function filterParseGB($term)
    {
        if(strpos($term, ' GB') !== false){
            return self::filterExtractFloat($term) * 1000;
        }

        return self::filterExtractFloat($term);
    }


    public static function get_provider_abbo_speed_down()
    {
        return [
            'kpn'             => ['4000' => '25', '9999999' => '50'],
            'hollandsnieuwe'  => ['9999999' => '14.4'],
            't-mobile'        => ['1000' => '15', '2500' => '30', '9999999' => '50'],
            'tele2'           => ['1000' => '14.4', '9999999' => '150'],
            'hi'              => ['1000' => '14.4', '6000' => '25', '9999999' => '50'],
            'vodafone'        => ['1000' => '7.2', '6000' => '30', '9999999' => '50'],
            'ben'             => ['9999999' => '14.4'],
            'sizz'            => ['9999999' => '7.2'],
            'simyo'           => ['9999999' => '14.4'],
            'telfort'         => ['9999999' => '7.2'],
            'mtv mobile'      => ['1000' => '15', '5000' => '30', '9999999' => '50'],
            'youfone'         => ['9999999' => '14.4'],
            'simpel'          => ['9999999' => '14.4'],
            'ziggo'           => ['9999999' => '225'],
            'upc'             => ['9999999' => '14.4'],
            'unitedconsumers' => ['1000' => '14.4', '9999999' => '150'],
        ];
    }

    public static function regexp_valid_name_chars($string)
    {
        return (substr(preg_replace("/[^A-Za-z0-9 \.]/", "", $string), 0, 25));
    }

    public static function isValidIBAN($iban)
    {

        $iban      = strtolower($iban);
        $Countries = [
            'al' => 28,
            'ad' => 24,
            'at' => 20,
            'az' => 28,
            'bh' => 22,
            'be' => 16,
            'ba' => 20,
            'br' => 29,
            'bg' => 22,
            'cr' => 21,
            'hr' => 21,
            'cy' => 28,
            'cz' => 24,
            'dk' => 18,
            'do' => 28,
            'ee' => 20,
            'fo' => 18,
            'fi' => 18,
            'fr' => 27,
            'ge' => 22,
            'de' => 22,
            'gi' => 23,
            'gr' => 27,
            'gl' => 18,
            'gt' => 28,
            'hu' => 28,
            'is' => 26,
            'ie' => 22,
            'il' => 23,
            'it' => 27,
            'jo' => 30,
            'kz' => 20,
            'kw' => 30,
            'lv' => 21,
            'lb' => 28,
            'li' => 21,
            'lt' => 20,
            'lu' => 20,
            'mk' => 19,
            'mt' => 31,
            'mr' => 27,
            'mu' => 30,
            'mc' => 27,
            'md' => 24,
            'me' => 22,
            'nl' => 18,
            'no' => 15,
            'pk' => 24,
            'ps' => 29,
            'pl' => 28,
            'pt' => 25,
            'qa' => 29,
            'ro' => 24,
            'sm' => 27,
            'sa' => 24,
            'rs' => 22,
            'sk' => 24,
            'si' => 19,
            'es' => 24,
            'se' => 24,
            'ch' => 21,
            'tn' => 24,
            'tr' => 26,
            'ae' => 23,
            'gb' => 22,
            'vg' => 24,
        ];

        $Chars = [
            'a' => 10,
            'b' => 11,
            'c' => 12,
            'd' => 13,
            'e' => 14,
            'f' => 15,
            'g' => 16,
            'h' => 17,
            'i' => 18,
            'j' => 19,
            'k' => 20,
            'l' => 21,
            'm' => 22,
            'n' => 23,
            'o' => 24,
            'p' => 25,
            'q' => 26,
            'r' => 27,
            's' => 28,
            't' => 29,
            'u' => 30,
            'v' => 31,
            'w' => 32,
            'x' => 33,
            'y' => 34,
            'z' => 35,
        ];


        if( ! isset($Countries[substr($iban, 0, 2)])){
            return false;
        }

        if(strlen($iban) != $Countries[substr($iban, 0, 2)]){
            return false;
        }

        $MovedChar      = substr($iban, 4) . substr($iban, 0, 4);
        $MovedCharArray = str_split($MovedChar);
        $NewString      = "";

        foreach($MovedCharArray as $k => $v){

            if( ! is_numeric($MovedCharArray[$k])){
                $MovedCharArray[$k] = $Chars[$MovedCharArray[$k]];
            }
            $NewString .= $MovedCharArray[$k];
        }
        if(function_exists("bcmod")){
            return bcmod($NewString, '97') == 1;
        }

        // http://au2.php.net/manual/en/function.bcmod.php#38474
        $x    = $NewString;
        $y    = "97";
        $take = 5;
        $mod  = "";

        do{
            $a   = (int) $mod . substr($x, 0, $take);
            $x   = substr($x, $take);
            $mod = $a % $y;
        }while(strlen($x));

        return (int) $mod == 1;
    }

    public static function array_get($arr, $key)
    {
        return isset($arr[$key]) ? $arr[$key] : 0;
    }

    public static function encode_dot($string)
    {
        return str_replace('.', '#DOT#', $string);
    }

    public static function comma_to_dot($string)
    {
        return str_replace(',', '.', $string);
    }

    public static function splitToArrayUppercase($string)
    {
        return strtoupper($string);
    }

    public static function doStringToLowercase($string)
    {
        return strtolower($string);
    }

    public static function delEmptyField($array)
    {
        return array_values($array);
    }

    //    public static function ingredientFilter($array)
    //    {

    //        $patternArray = ['/biologisch/'                  => '100% biologisch',
    //                         '/eko gecertificeerd/'          => '100% biologisch',
    //                         '/eko-keurmerk/'                => '100% biologisch',
    //                         '/(community.)/'                => 'community',
    //                         '/seizoen/'                     => 'Seizoensproducten',
    //                         '/100% van nederlandse boeren/' => 'Van NL boodem',
    //                         '/hollands/'                    => 'Van NL boodem',
    //                         '/herkomst/'                    => 'Van NL boodem',
    //                         '/nederlands/'                  => 'Van NL boodem',
    //                         '/organic/'                     => 'Organic',
    //                         '/dagvers/'                     => 'Dagvers',
    //                         '/detox sappen/'                => 'Detox',
    //                         '/fairtrade/'                   => 'Fairtrade',
    //                         '/raw/'                         => 'Raw',
    //                         '/detox tips/'                  => 'Detox',
    //                         '/vega/'                        => 'vegan'];
    //
    //
    //        $array = self::adjustCategory($array, $patternArray);
    //
    //
    //        foreach ($array as $key => $value)
    //            $array[$key] = preg_replace('/^(\s)/', '', $value);
    //
    //        return array_values(array_filter($array));
    //
    //        return $array;
    //    }

    public static function doubleElementsInDiffCategory()
    {

    }


    //    public static function allergicInfoFilter($array)
    //    {
    //        $patternArray = ['/ja/' => 'informatie beschikbaar'];
    //        $array = self::adjustCategory($array, $patternArray);
    //        return array_values($array);
    //        return $array;
    //    }

    public static function delElement($array, $searchString)
    {
        foreach($array as $key => $value){
            if($value == $searchString){
                unset($array[$key]);
            }
        }

        return array_values($array);
    }

    /**
     * Filter out the FIRST ELEMENT and remove duplicated SECONDONE
     *
     * @param $array
     *
     * @return array
     */
    public static function delDoubleElement($array, $searchArray)
    {
        foreach($searchArray as $first => $second){
            if(in_array($first, $array)){

                if(in_array($second, $array)){
                    $key = array_search($second, $array);
                    unset($array[$key]);
                }
            }
        }

        return array_values($array);
    }

    public static function delWhitespace($string)
    {
        return str_replace(" ", "", $string);
    }


    public static function normalizeDeliveryDay($string)
    {

        $pattern         = '[t/m]';
        $replace         = ',';
        $MinMaxDay       = [];
        $returnString    = $string;
        $daysOfWeekArray = ["maandag", "dinsdag", "woensdag", "donderdag", "vrijdag", "zaterdag", "zondag"];

        if(preg_match($pattern, $string)){
            $string   = preg_replace($pattern, $replace, $string);
            $DayArray = array_map('trim', explode(",", $string));

            foreach($DayArray as $Value){
                $MinMaxDay[] = array_search($Value, $daysOfWeekArray);
            }

            if($MinMaxDay[0] > $MinMaxDay[1]){
                $amountOfDays = (7 - $MinMaxDay[0]) + $MinMaxDay[1];
            }else{
                $amountOfDays = $MinMaxDay[1] - $MinMaxDay[0];
            }

            $returnString = $daysOfWeekArray[$MinMaxDay[0]];
            $counter      = 1;
            while($amountOfDays != 0){
                if(($MinMaxDay[0] + $counter) > 6){
                    $MinMaxDay[0] = 0;
                    $counter      = 0;
                }

                $returnString .= "," . $daysOfWeekArray[$MinMaxDay[0] + $counter];
                $counter ++;
                $amountOfDays --;
            }
        }

        $pattern = '/bijvoorbeeld[,:*]/';
        $replace = '';
        if(preg_match($pattern, $returnString)){
            $returnString = preg_replace($pattern, $replace, $returnString);
        }

        return $returnString;
    }

    //
    //    public static function momentOfDeliveryFilter($array)
    //    {
    //        $patternArray = ['/ochtend/' => 'ochtend',
    //                         '/middag/'  => 'middag',
    //                         '/avond/'   => 'avond'];
    //
    //        $array = self::adjustCategory($array, $patternArray);
    //
    //        return array_values($array);
    //    }


    public static function adjustCategory($array, $patternArray)
    {
        $MomentArray = $array;
        foreach($MomentArray as $Key => $Value){
            foreach($patternArray as $patter => $replacement){
                if(preg_match($patter, $Value)){
                    $array[$Key] = $replacement;
                }
            }
        }

        return array_values($array);
    }


    public static function split_to_array($string)
    {
        if(is_array($string)){
            return;
        }

        // remove trailing , or | symbols to prevent getting an empty array member
        $string      = trim($string);
        $end_pattern = '/,$|\|$/';
        $end_replace = '';
        if(preg_match($end_pattern, $string)){
            $string = preg_replace($end_pattern, $end_replace, $string);
        }

        // normalize pipe symbol | to comma
        $pattern = '/\|/';
        $replace = ',';
        if(preg_match($pattern, $string)){
            $string = preg_replace($pattern, $replace, $string);
        }

        // normalize slash symbol / to comma
        $pattern = '[/]';
        $replace = ',';
        if(preg_match($pattern, $string)){
            $string = preg_replace($pattern, $replace, $string);
        }

        $returnArray = array_values(explode(",", $string));
        $returnArray = array_map('trim', $returnArray);

        return array_values($returnArray);
    }

    public static function breadcrumbs_to_array($string)
    {
        // [category_path] => Dames - Schoenen - Pumps - Klassieke pumps
        //        Heren - Outlet - Schoenen - Sneakers - Sneakers laag

        $string      = trim($string);
        $end_pattern = '/\/$|>$/';
        $end_replace = '';
        if(preg_match($end_pattern, $string)){
            $string = preg_replace($end_pattern, $end_replace, $string);
        }

        $pattern = '/\/|>/';
        $replace = '-';
        if(preg_match($pattern, $string)){
            $string = preg_replace($pattern, $replace, $string);
        }

        return array_map('trim', explode("-", $string));

    }


    public static function check_stock($string)
    {
        //ValueInterface::INFINITE
        $pattern     = '/^([^0].*)/i';
        $replacement = 'In stock';

        return preg_replace($pattern, $replacement, $string);

    }

    public static function getUKtoEUSizeMen()
    {
        return [
            'UK2'    => '35',
            'UK2.3'  => '35.5',
            'UK3'    => '36',
            'UK3.5'  => '36.5',
            'UK4'    => '37',
            'UK4.5'  => '37.5',
            'UK5'    => '38',
            'UK5.5'  => '38.5',
            'UK6'    => '39',
            'UK6.5'  => '40',
            'UK7'    => '40.5',
            'UK7.5'  => '41',
            'UK8'    => '42',
            'UK8.5'  => '43',
            'UK9'    => '43.5',
            'UK9.5'  => '44',
            'UK10'   => '44.5',
            'UK10.5' => '45',
            'UK11'   => '45.5',
            'UK11.5' => '46',
            'UK12'   => '46.5',
            'UK12.5' => '47',
            'UK13'   => '47.5',
            'UK13.5' => '48.5',
            'UK14'   => '49',
            'UK15'   => '50',
            'UK16'   => '51',
        ];
    }

    public static function getUKtoEUSizeWomen()
    {
        return [
            'UK2'    => '35',
            'UK2.5'  => '35',
            'UK3'    => '35.5',
            'UK3.5'  => '36',
            'UK4'    => '36.5',
            'UK4.5'  => '37',
            'UK5'    => '37.5',
            'UK5.5'  => '38',
            'UK6'    => '38.5',
            'UK6.5'  => '39',
            'UK7'    => '39.5',
            'UK7.5'  => '40',
            'UK8'    => '40.5',
            'UK8.5'  => '41',
            'UK9'    => '41.5',
            'UK9.5'  => '42',
            'UK10'   => '42.5',
            'UK10.5' => '43',
            'UK11'   => '43.5',
            'UK11.5' => '44',
            'UK12'   => '44,5',
            'UK13'   => '45',
        ];
    }


    public static function convertUKtoEUSize($string, $gender)
    {

        $menSizeUKtoEU   = self::getUKtoEUSizeMen();
        $womenSizeUKtoEU = self::getUKtoEUSizeWomen();

        if($gender == 'male'){
            if(array_key_exists($string, $menSizeUKtoEU)){
                return $menSizeUKtoEU[$string];
            }
        }else{
            if(array_key_exists($string, $womenSizeUKtoEU)){
                return $womenSizeUKtoEU[$string];
            }
        }

        return $string;

    }

    public static function renameCategory($data, $arrayFilter)
    {
        $arrayData = $data;
        foreach($arrayData['tags'] as $dataKey => $dataValue){
            foreach($arrayFilter as $filterPreg => $filterValue){
                if(preg_match($filterPreg, $dataValue)){
                    $arrayData['tags'][$dataKey] = preg_replace($filterPreg, $filterValue, $dataValue);
                    break;
                }
            }
        }

        return $arrayData;
    }

    public static function multiPregReplace($string, $arrayPregReplace)
    {
        foreach($arrayPregReplace as $pattern => $replacement){
            $string = preg_replace($pattern, $replacement, $string);
        }

        return $string;
    }

    public static function replaceCharacter($string, $arrayPregReplace)
    {
        return self::multiPregReplace($string, $arrayPregReplace);
    }

    public static function filterOutDataSet($data, $fieldName, $arrayFilter, $errorMassage = 'error in this Dataset')
    {
        $arrayData = $data;
        foreach($arrayData[$fieldName] as $keyTag => $valueTag){
            foreach($arrayFilter as $filter){
                if($valueTag == $filter){
                    $arrayData['error'] = $errorMassage;
                }
            }
        }

        return $arrayData;
    }

    public static function pregfilterOutDataSet($data, $fieldName, $arrayFilter, $errorMassage = 'error in this Dataset')
    {
        $arrayData = $data;
        if(is_array($arrayData[$fieldName])){
            foreach($arrayData[$fieldName] as $keyTag => $valueTag){
                foreach($arrayFilter as $filter){
                    if(preg_match($filter, $valueTag)){
                        $arrayData['error'] = $errorMassage;
                    }
                }
            }
        }elseif(is_string($arrayData[$fieldName])){
            foreach($arrayFilter as $filter){
                if(preg_match($filter, $arrayData[$fieldName])){
                    $arrayData['error'] = $errorMassage;
                }
            }
        }

        return $arrayData;
    }

    public static function multiPregMatch($string, $arrayFilter)
    {
        foreach($arrayFilter as $filterVal){
            if(preg_match($filterVal, $string)){
                return 1;
            }
        }

        return 0;
    }


    public static function getGenderDefinition($genderString, Array $addGenderArray = null)
    {

        $genderDefinitionArray = [
            'kinderen babyschoenen jongen' => 'Baby',
            'kinderen babyschoenen meisje' => 'Baby',
            'kinderen jongens'             => 'Jongens',
            'kinderen meisjes'             => 'Meisjes',
            'kinderen'                     => 'Kinderen',
            'jongens'                      => 'Jongens',
            'boy'                          => 'Jongens',
            'boys'                         => 'Jongens',
            'meisjes'                      => 'Meisjes',
            'girl'                         => 'Meisjes',
            'girls'                        => 'Meisjes',
            'male'                         => 'Heren',
            'heren'                        => 'Heren',
            'female'                       => 'Dames',
            'ladies'                       => 'Dames',
            'dames'                        => 'Dames',
            'kinderen unisex'              => 'Kinderen',
            'unisex'                       => 'Unisex',
        ];

        if(is_null($genderString) or is_array($genderString)){
            return null;
        }

        //add extern gender definitions
        if( ! empty($addGenderArray)){
            $genderDefinitionArray = array_merge($genderDefinitionArray, $addGenderArray);
        }

        $genderString = strtolower($genderString);
        if(array_key_exists($genderString, $genderDefinitionArray)){
            $genderString = $genderDefinitionArray[$genderString];
        }else{
            print_r($genderString . '|');
        }

        return $genderString;
    }

    public static function getColorDefinition()
    {

        return [
            'Zwart'  => [
                'zwart',
                'standard',
                'charcoal black',
                'charcoal black',
                'blk',
                'miro black',
                'metallic black',
                'zwart denim/blackdenim',
                'black suede',
                'borg and black',
                '0010 black',
                'zwartlak',
                'black high shine',
                'zwartmetaal',
                'zwart croco lak',
                'black mix',
                'peplum-zwart',
                'noir',
                'black patent',
                'full black',
                'graphite',
                'black',
                'N/A',
                'zwart-metallic'
            ],
            'Wit'    => [
                'pure white',
                'full white',
                'miro white',
                'white mix',
                'ivory',
                'foam',
                'chic white',
                'zand',
                'vanille',
                'blanc',
                'ecru',
                'wit',
                'mastic',
                'white',
                'gebroken wit',
                'chantilly lace',
                'gebroken-wit',
                'ivoorwit'
            ],
            'Beige'  => [
                'zandkleurig',
                'taupe zebra',
                'kakimat',
                'creme',
                'tannubuc',
                'cream',
                'open beige',
                'amandelindigo',
                'driftwood',
                'klei',
                'biscuit',
                'mosterdkaki',
                'noisetteparel',
                'ginger',
                'mandelindigo',
                'beige',
                'antiek',
                'nude',
                'sand',
                'tan',
            ],
            'Roze'   => [
                'la fleur',
                'roze',
                'rose',
                'rose gold',
                'mauve',
                'damson',
                'pink',
                'rosa',
                'Violet',
                'lichtroze',
                'blush',
                'bleekroze',
                'pastelroze',
                'geraniumroze',
                'coral mix',
                'kauwgumroze',
                'huidskleur',
                'lavendel',
                'pink mix',
                'fuchsia',
                'pale pink',
                'blush pink',
                'blush velvet',
                'perzik',
                'berry',
                'licht/pastelpaars',
                'zalmkleurig',
                'veenbes',
                'grapefruit',
                'framboossorbet',
                'koraal',
                'violet',
                'zalm',
                'pruim',
                'bright pink',
                'braam',
                'framboos',
                'fushia',
                'druif',
            ],
            'Rood'   => [
                'rose red',
                'rood',
                'burg',
                'burg velvet',
                'gebloktrood',
                'red',
                'crimson',
                'bright coral',
                'coral',
                'wijnrood',
                'rood+zilver',
                'bordeau',
                'strokoraal',
                'ruby-amber',
                'bordeaufushia',
                'dark burgundy',
                'burgundy mix',
                'earthbordeau',
                'cherry red arcadia',
                'ginger',
                'marinebordeau',
                'donkerrood',
                'red suedette',
                'paislybordeaux',
                'red mix',
                'kersenrood',
                'middenrood',
                'dieprood',
                'bordeaux mêleerd',
                'wine',
                'koraalrood',
                'koraal rood',
                'ruby',
                'oxblood',
                'cherry',
                'burgundy',
                'aardbeirood',
                'vermiljoen',
                'chili',
                'bordeaux',
            ],
            'Paars'  => [
                'paars',
                'purple',
                'magenta',
                'pastelpaars',
                'lila mêleerd',
                'aubergine',
                'lichtpaars',
            ],
            'Groen'  => [
                'groen',
                'green',
                'munt',
                'neongroen',
                'verde',
                'lime',
                'cyan',
                'darkolive',
                'korstmos',
                'green mix',
                'aguamar',
                'gunmetal',
                'chartreuse',
                'smaragdgroen',
                'biljartgroen',
                'olijforanje',
                'olijf mêleerd',
                'olijfgroen',
                'pastelgroen',
                'groen-metallic',
                'khaki mix',
                'middengroen',
                'army',
                'bosgroen',
                'groenavocado',
                'mint',
                'lichtgroen',
                'mintgroen',
                'olijf',
                'kaki',
                'khaki',
                'stone'
            ],
            'Grijs'  => [
                'spacegrijs',
                'taupe',
                'space gray',
                'smog',
                'zilvergrijs',
                'mink',
                'mink mix',
                'taupeiris',
                'feather',
                'metaal',
                'carbon',
                'charcoal',
                'space grey',
                'd55 moonlight',
                'blauwgrijs',
                'slate grey',
                'mistero',
                'metallic',
                'licht grijs',
                'grey mix',
                'gun grey',
                'dark grey',
                'metallic grey',
                'dark metal',
                'graniet',
                'gris',
                'grijs',
                'gray',
                'grey',
                'titan',
                'metal',
                'mediumgrijs',
                'lichtgrijs',
                'pastelgrijs',
                'smoke',
                'licht/pastelgrijs',
                'hoofd',
                'donkergrijs',
                'open grijs',
                'steen',
                'grijs denim',
                'rock',
                'ijsgrijs',
                'middengrijs',
            ],
            'Geel'   => [
                'geel',
                'yellow',
                'zwavelgeel',
                'neongeel',
                'graan',
                'graannubuck',
                'whewheat',
                'pastelgeel',
                'lichtgeel',
                'mosterd',
                'donkergeel',
                'oker'
            ],
            'Oranje' => [
                'orange flare',
                'nudeoranje',
                'oranje',
                'curry',
                'apricot',
                'orange',
                'oranjebruin',
                'oranjepop',
                'mandarijn',
                'abricoos',
                'coraal',
                'donkeroranje'
            ],
            'Bruin'  => [
                'bruin',
                'brown',
                'tabaksbruin',
                'chocolate',
                '2202 chocolate',
                'noisetteparel kluer',
                'licht/pastelbruin',
                'chestnut suede',
                'mocca',
                'lichtbruin',
                'leer',
                'conker',
                'gingerbread',
                'walnut',
                'brown leather',
                'ebbehout',
                'middenbruin',
                'ebène',
                'caramel',
                'espresso',
                'espressokaki',
                'choco',
                'choc',
                'chocoladebruin',
                'tabacco',
                'Tabaco',
                'maroon',
                'marron',
                'burnt umber',
                'brandy',
                'roest',
                'truffle',
                'fauve',
                'roestleer',
                't.moro',
                'donkerbruin',
                'rustiek',
                'camelzebra',
                'donker bruin',
                'honey',
                'schildpad',
                'brown high shine',
                'noisette',
                'brown mix',
                'chestnut',
                'graanchocolat',
                'chocolat',
                'hazelhighwaypendleton',
                'oxblood box',
                'pepperantic',
                'middel bruin',
                'natural',
                'mahogany',
                'koper',
                'mushroom',
                'amandel',
                '2216 camel',
                'camel',
                'dark tan',
                'amber',
                'karamel',
                'stout',
                'zwart-bruin',
                '2249 tobacco',
                'BAMBOO',
                '2506 vintage tan',
                'dark brown',
                'COPPER',
                'mokka',
                'koffie',
                'cognac'
            ],
            'Brons'  => [
                'bronze',
                'copper metallic',
                'brons',
                'copper',
                'brons denim',
                'bronsprint',
                'gelamelleerdbrons',
                'brons-meerkleurig',
                'bronsslang',
                'sparkbrons'
            ],
            'Goud'   => [
                'goud',
                'gold',
                'rozegoud',
                'rosé goud',
                'champagne',
                'goudbruin',
                'goudrozekleurig',
                'platinum',
                'goudkleurig',
                'goudkleur'
            ],
        ];
    }

    /**
     * Infofolio converts
     * TODO: move to some nice convertor
     */
    public static function filterInfoMVHouseOwner($value)
    {
        return ($value == "Koop") ? 1 : 0;
    }

    public static function filterInfoMVHouseUsage($value)
    {
        if($value == 'Woonfunctie'){
            return 2;
        }
        //choice:1=Overig,2=Wonen,4=Wonen en deels kamerverhuur,5=Wonen en deels bedrijfspand,6=Wonen en deels winkel,7=Wonen en deels kantoor,8=Wonen en deels horeca',
        $functies = explode(',', $value);
        // geen woonfunctie -> overig
        if( ! in_array('Woonfunctie', $functies)){
            return 1;
        }
        if(in_array('Andere gebruiksfunctie', $functies)){
            return 1;
        }

        //sowieso dus woonfunctie
        if(in_array('Bijeenkomstfunctie', $functies)){
            return 8;
        }
        if(in_array('Celfunctie', $functies)){
            return 5;
        }
        if(in_array('Gezondheidsfunctie', $functies)){
            return 5;
        }
        if(in_array('Industriefunctie', $functies)){
            return 5;
        }
        if(in_array('Kantoorfunctie', $functies)){
            return 7;
        }
        if(in_array('Logiesfunctie', $functies)){
            return 4;
        }
        if(in_array('Onderwijsfunctie', $functies)){
            return 7;
        }
        if(in_array('Sportfunctie', $functies)){
            return 7;
        }
        if(in_array('Winkelfunctie', $functies)){
            return 6;
        }
        return 1;
    }

    //11 = 1-4 kamers 12 = meer dan 4 kamers
    //Voor 2 onder 1 kap-woningen: 21 = 1-3 kamers 22 = 4-6 kamers 23 = meer dan 6 kamers
    //Voor appartementen, rijtjes- en hoekwoningen: 31 = 1-2 kamers 32 = 3-5 kamers 33 = 6-8 kamers 34 = meer dan 8 kamers

    public static function filterInfoMVRoomCount($value)
    {
        switch($value){
            case 11:
                return 2;
            case 12:
                return 5;
            case 21:
                return 2;
            case 22:
                return 5;
            case 23:
                return 7;
            case 31:
                return 2;
            case 32:
                return 4;
            case 33:
                return 7;
            case 34:
                return 9;
        }
        return 0;
    }

    public static function filterInfoMVHouseType($value)
    {
        $mapping = [
            "Woning"                                          => 2,
            "Eengezinswoning"                                 => 2,
            "Vrijstaande woning"                              => 2,
            "2 onder 1 kap woning"                            => 2,
            "Tussen/rijwoning"                                => 2,
            "Hoekwoning"                                      => 2,
            "Eindwoning"                                      => 2,
            "Geschakelde woning"                              => 2,
            "Geschakelde 2 onder 1 kapwoning"                 => 2,
            "Meergezinswoning"                                => 2,
            "Appartement"                                     => 16,
            "Galerijflat"                                     => 16,
            "Maisonnette"                                     => 2,
            "Portiekflat"                                     => 16,
            "Portiekwoning"                                   => 16,
            "Benedenwoning"                                   => 18,
            "Bovenwoning"                                     => 18,
            "Corridorflat"                                    => 16,
            "Woonwagen/-boot"                                 => 1,
            "Woonwagen/-boot recreatief"                      => 1,
            "Woonwagen/stacaravan"                            => 1,
            "Woonwagen/stacaravan recreatief"                 => 1,
            "Standplaats"                                     => 1,
            "Standplaats recreatief"                          => 1,
            "Woonboot"                                        => 1,
            "Woonboot recreatief"                             => 1,
            "Ligplaats"                                       => 1,
            "Ligplaats recreatief"                            => 1,
            "Waterwoning"                                     => 2,
            "Waterwoning recreatief"                          => 2,
            "Recreatiewoningen"                               => 2,
            "Vrijstaande recreatiewoning"                     => 2,
            "2 onder 1 kap recreatiewoning"                   => 2,
            "Tussen/rij recreatiewoning"                      => 2,
            "Hoek recreatiewoning"                            => 2,
            "Eind recreatiewoning"                            => 2,
            "Geschakelde recreatiewoning"                     => 2,
            "Geschakelde 2 onder 1 kap recreatiewoning"       => 2,
            "Doelgroepwoning"                                 => 2,
            "Vrijstaande doelgroepwoning"                     => 2,
            "2 onder 1 kap doelgroepwoning"                   => 2,
            "Tussen/rij doelgroepwoning"                      => 2,
            "Hoek doelgroepwoning"                            => 2,
            "Eind doelgroepwoning"                            => 2,
            "Geschakelde doelgroepwoning"                     => 2,
            "Geschakelde 2 onder 1 kap doelgroepwoning"       => 16,
            "Meergezins doelgroepwoning"                      => 2,
            "Galerijflat doelgroep"                           => 16,
            "Maisonnette doelgroep"                           => 16,
            "Portiekflat doelgroep"                           => 16,
            "Portiekwoning doelgroep"                         => 16,
            "Benedenwoning doelgroep"                         => 18,
            "Bovenwoning doelgroep"                           => 18,
            "Corridorflat doelgroep"                          => 16,
            "Garage(box)"                                     => 1,
            "Specifiek woonobject"                            => 1,
            "Overig woonobject"                               => 1,
            "Niet-woning met woongedeelte"                    => 1,
            "Detailhandel/Horeca/Kantoren met woongedeelte"   => 1,
            "Detail/groothandel met woongedeelte"             => 1,
            "Horeca met woongedeelte"                         => 1,
            "Kantoor met woongedeelte"                        => 1,
            "Laboratoria en praktijk met woongedeelte"        => 1,
            "Bedrijf met woongedeelte"                        => 1,
            "Agrarisch met woongedeelte"                      => 1,
            "Gemeenschappelijke voorziening met woongedeelte" => 1,
            "Onderwijs met woongedeelte"                      => 1,
            "Medisch met woongedeelte"                        => 1,
            "Bijzondere woonfunctie met woongedeelte"         => 1,
            "Gemeenschapsgebouw overig met woongedeelte"      => 1,
            "Cultuur en religie met woongedeelte"             => 1,
            "Cultuur met woongedeelte"                        => 1,
            "Religie met woongedeelte"                        => 1,
            "Sport en recreatie met woongedeelte"             => 1,
            "Nutsvoorziening met woongedeelte"                => 1,
            "Energie en water met woongedeelte"               => 1,
            "Transport met woongedeelte"                      => 1,
            "Communicatie met woongedeelte"                   => 1,
            "Defensie met woongedeelte"                       => 1,
            "Niet-woning"                                     => 1,
            "Detail/groothandel"                              => 1,
            "Detailhandel/Horeca/Kantoor"                     => 1,
            "Horeca"                                          => 1,
            "Kantoor"                                         => 1,
            "Laboratoria en praktijk"                         => 1,
            "Bedrijf"                                         => 1,
            "Agrarisch"                                       => 1,
            "Gemeenschappelijke voorziening"                  => 1,
            "Onderwijs"                                       => 1,
            "Medisch"                                         => 1,
            "Bijzondere woonfunctie"                          => 1,
            "Gemeenschapsgebouw overig"                       => 1,
            "Cultuur en religie"                              => 1,
            "Cultuur"                                         => 1,
            "Religie"                                         => 1,
            "Sport en recreatie"                              => 1,
            "Nutsvoorziening"                                 => 1,
            "Energie en water"                                => 1,
            "Transport"                                       => 1,
            "Communicatie"                                    => 1,
            "Defensie"                                        => 1
        ];
        return isset($mapping[$value]) ? $mapping[$value] : 1;

    }

    public static function filterInfoMVRoofMaterial($value)
    {
        $mapping = [
            10 => 1,
            11 => 2,
            12 => 1,
            13 => 1,
            14 => 1,
            15 => 1,
            16 => 1,
            17 => 2,
            18 => 2,
            19 => 2,
            20 => 1,
            21 => 2,
            30 => 1,
            31 => 4,
            50 => 2,
            51 => 2,
            70 => 2,
            71 => 1,
            72 => 1,
            73 => 2,
            74 => 2
        ];
        return isset($mapping[$value]) ? $mapping[$value] : 1;
    }


}