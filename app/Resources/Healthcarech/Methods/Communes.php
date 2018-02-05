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

class Communes extends AbstractMethodRequest
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
        $my_conv  = function ($str) {
            return mb_convert_encoding(str_replace('\n', PHP_EOL, $str), 'UTF-8');
        };
        $communes = [];
        $count    = 0;
        foreach(FileHelper::read_lines(__DIR__ . '/../Files/communes.tsv', true) as $c){
            if($c[0] != '*'){
                continue;
            }
            $kanton                            = $c[3];
            $commune                           = $my_conv($c[6]);
            $commune                           = preg_replace('/(\([A-Z]{2}\))/', '', $commune);
            $ort                               = $my_conv($c[2]);
            $ort                               = preg_replace('/( [A-Z]{2})/', '', $ort);
            $label                             = $ort . ' (' . $kanton . ') ' . $commune;
            $postal_code                       = (int) $c[1];
            $region                            = (int) $c[4];
            $key                               = $region.'_'.$c[5];
            //$kanton                            = $c[];
            $communes[$postal_code][$region][] = ['label' => $label, 'key' => $key, 'kanton' => $kanton];
            $count ++;
        }
        $returnList = [];
        foreach($communes as $postalcode => $regions){
            if(count($regions) === 1 and count($regions[array_keys($regions)[0]]) === 1){
                continue;
            }
            $regionList = [];
            foreach($regions as $region => $labelList){
                foreach($labelList as $fields){
                    $regionList[] = [
                        ResourceInterface::POSTAL_CODE => $postalcode,
                        ResourceInterface::LABEL       => $fields['label'],
                        ResourceInterface::NAME        => $fields['key'],
                        ResourceInterface::REGION      => $region,
                        ResourceInterface::KANTON      => $fields['kanton'],
                    ];
                }
            }
            $regionList = array_values(array_sort($regionList, function ($value) {
                return $value[ResourceInterface::LABEL];
            }));
            $returnList = array_merge($returnList, $regionList);
        }
        return $returnList;
    }

    public function executeFunction()
    {
        //do nothing
    }
}