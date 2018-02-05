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
use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;

class Hmos extends AbstractMethodRequest
{
    public function __construct()
    {
        $this->strictStandardFields = false;
        $this->cacheDays            = false;
    }



    //colls:
    //Versicherer	Kanton	Geschäftsjahr	Region	HMO	HMO-ID	HMO-Name	Strasse	PLZ	Ort

    public function getResult()
    {
        $my_conv = function ($str) {
            return mb_convert_encoding(str_replace('\n', PHP_EOL, $str), 'UTF-8');
        };
        $hmos    = [];
        $count   = 0;
        foreach(FileHelper::read_lines(__DIR__ . '/../Files/hmo_address.tsv', true) as $c){
            preg_match('!\d+!', $c[3], $matches);
            $region = $matches[0];
            $hmos[] = [
                /* 00 */
                'company_id'                   => (int) $c[0],
                /* 01 */
                'kanton'                       => $c[1],
                /* 02 */
                'year'                         => (int) $c[2],
                /* 03 */
                'region'                       => (int) $region,
                /* 04 */
                'hmo'                          => $c[4],
                /* 05 */
                'hmo_uid'                      => $c[5],
                /* 06 */
                'label'                        => $c[6],
                /* 07 */
                ResourceInterface::STREET      => $my_conv($c[7]),
                /* 08 */
                ResourceInterface::POSTAL_CODE => (int) $c[8],
                /* 09 */
                ResourceInterface::CITY        => $my_conv($c[9]),
            ];
            $count ++;
        }

        return $hmos;
    }

    public function executeFunction()
    {
        //do nothing
    }
}