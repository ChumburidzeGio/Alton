<?php
/**
 * Created by PhpStorm.
 * User: kostya
 * Date: 29/04/16
 * Time: 13:38
 */

namespace App\Resources\Healthcarech\Methods;


use App\Helpers\FileHelper;
use App\Helpers\HCCHHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;

class Products extends AbstractMethodRequest
{
    public function __construct()
    {
        $this->strictStandardFields = false;
        $this->cacheDays            = false;
    }


    public function getResult()
    {
        $companies = [];
        foreach (FileHelper::read_lines(__DIR__ . '/../Files/companies.csv') as $c) {
            $companies[(int) $c[0]] = [
                'id'      => (int) $c[0],
                'name'    => mb_convert_encoding(str_replace('<br>', PHP_EOL, $c[1]), 'UTF-8'),
                'info'    => mb_convert_encoding(str_replace('<br>', PHP_EOL, $c[2]), 'UTF-8'),
                'iso'     => (int) $c[3],
                'gln_ean' => (int) $c[4],
                'sasis'   => (bool) str_replace('<br>', '', $c[5]),
                'ofac'    => (bool) str_replace('<br>', '', $c[6]),
            ];
        }

        $records = [];
        foreach (FileHelper::read_lines(__DIR__ . '/../Files/premiums_2017.csv', false, ';') as $n => $t) {
            $franchise   = substr($t[12], 4);
            $tariff_type = substr($t[9], 4);
            $tarif       = $t[8];
            $company     = (int) $t[0];

            if ($tarif === 'NetMed_10' and $company === 1509) {
                $tariff_type = 'HMO';
            }


            $records[] = [
                'title'                     => $companies[(int) $t[0]]['name']
                                               . ' | ' . substr($t[6], -3) . ' ' . $tariff_type . ' | '
                                               . ' Franchise: fr. ' . $franchise . ',-'
                                               . ' (' . $t[1] . ' CH' . HCCHHelper::REGIONS[$t[5]] . ')',
                'company'                   => $company,
                'kanton'                    => $t[1],
                'region'                    => HCCHHelper::REGIONS[$t[5]],
                'age'                       => HCCHHelper::AGES[$t[6]],
                ResourceInterface::ACCIDENT => $t[7] === 'MIT-UNF',
                'price'                     => (float) $t[13],
                'tarif'                     => $tarif,
                'tarif_type'                => $tariff_type,
                'franchise'                 => (int) $franchise,
                'base_premie'               => $t[14] === '1',
                'base_franchise'            => $t[15] === '1',
                'altersuntergruppe'         => $t[10],

            ];
        }

        return $records;

    }

    public function executeFunction()
    {
        //do nothing
    }
}