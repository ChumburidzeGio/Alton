<?php
/**
 * Created by PhpStorm.
 * User: kostya
 * Date: 29/04/16
 * Time: 13:38
 */

namespace App\Resources\Healthcarech\Methods;


use App\Helpers\FileHelper;
use App\Interfaces\ResourceInterface;
use App\Resources\AbstractMethodRequest;

class Regions extends AbstractMethodRequest
{
    public function __construct()
    {
        $this->strictStandardFields = false;
        $this->cacheDays            = false;
    }


    public function getResult()
    {
        $regions = [];
        foreach (FileHelper::read_lines(__DIR__ . '/../Files/kanton_region.csv') as $r) {

            $regions[] = [
                'code'                         => 'CH' . $r[2] . $r[3],
                ResourceInterface::POSTAL_CODE => (int) $r[0],
                'name'                         => $r[1],
                'kanton'                       => $r[2],
                'region'                       => (int) $r[3],
                'bfs'                          => (int) $r[4],
                ResourceInterface::CITY        => $r[5],
                'district'                     => $r[6],
            ];
        }

        return $regions;
    }

    public function executeFunction()
    {
        //do nothing
    }
}