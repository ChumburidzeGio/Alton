<?php
/**
 * Created by PhpStorm.
 * User: kostya
 *
 * Date: 29/04/16
 * Time: 13:38
 */

namespace App\Resources\Healthcarech\Methods;


use App\Helpers\FileHelper;
use App\Resources\AbstractMethodRequest;

class Companies extends AbstractMethodRequest
{
    public function __construct()
    {
        $this->strictStandardFields = false;
        $this->cacheDays            = false;
    }


    public function getResult()
    {
        $my_conv   = function ($str, $allownv = false) {
            if((($str == 'n.v.') && ! $allownv) || ($str == '-')){
                return "";
            }
            return mb_convert_encoding(str_replace('\n', PHP_EOL, $str), 'UTF-8');
        };
        $companies = [];
        $var       = 1;
        foreach(FileHelper::read_lines(__DIR__ . '/../Files/komparu_data7.tsv', true) as $c){
            if(str_contains($c[0], 'KOMPARU')){
                continue;
            }


            if( ! isset($c[2])){
                continue;
            }
            if( ! isset($c[4])){
                continue;
            }
            //not active
            if( ! isset($c[12])){
                continue;
            }

            $title      = $my_conv($c[2]);
            $belongs_to = $my_conv($c[4]);
            $address    = $my_conv($c[9]);
            $conditions = $my_conv($c[10]);
            $image      = '//code.komparu.com/userfiles/logo/' . strtolower($c[11]);
            if( ! ends_with($c[11], '.png')){
                $image .= '.png';
            }
            $description_de = $my_conv($c[12]);
            $description_en = $my_conv($c[13]);
            $reviews        = $my_conv($c[14]);
            $positive       = isset($c[20]) ? $my_conv($c[20], true) : 'n.v.';
            $negative       = isset($c[21]) ? $my_conv($c[21], true) : "";
            $abreviation    = $my_conv($c[3]);


            $row = [
                /* 00 */
                '__id'                           => (int) $c[1],
                /* 00 */
                'knip_id'                        => (int) $c[0],
                /* __ */
                'name'                           => preg_replace('/[^a-z0-9]+/', '_', strtolower($title)),
                /* 01 */
                'title'                          => $title,
                /* 02 */
                'belongs_to'                     => $belongs_to,
                /* 03 */
                'purchase'                       => $this->convertGermanToBool($c[6]),
                /* 04 */
                'contract'                       => $this->convertGermanToBool($c[7]),
                /* 05 */
                'comparison'                     => $this->convertGermanToBool($c[8]),
                /* 06 */
                'address'                        => $address,
                /* 07 */
                'conditions'                     => $conditions,
                /* 08 */
                'image'                          => $image,
                /* 09 */
                'description_de'                 => $description_de,
                /* 10 */
                'description_en'                 => $description_en,
                /* 11 */
                'reviews'                        => $reviews,
                /* 12 */
                'gesat_rating'                   => isset($c[15]) ? (float) $c[15] : 0,
                /* 13 */
                'tage_bis_ruckerstattung'        => isset($c[16]) ? (float) $c[16] : 0,
                /* 13 */
                'kundenrating'                   => isset($c[18]) ? (float) $c[18] : 0,
                /* 14 */
                'geschwindigkeit_ruckerstattung' => isset($c[17]) ? (float) $c[17] : 0,
                /* 15 */
                'knip_kollaboration'             => isset($c[19]) ? (float) $c[19] : 0,
                /* 18 */
                'positive'                       => $positive,
                /* 19 */
                'negative'                       => $negative,
                /* 19 */
                'email'                          => isset($c[22]) ? $c[22] : "n/a",

                'abreviation' => $abreviation,
            ];
            // dd($row);
            $companies[] = $row;
            $var ++;

        }

        return $companies;
    }

    private function convertGermanToBool($term)
    {
        $term = strtolower(trim($term));

        return (($term == 'ja') || ($term == 'yes'));
    }

    public function executeFunction()
    {
        //do nothing
    }
}