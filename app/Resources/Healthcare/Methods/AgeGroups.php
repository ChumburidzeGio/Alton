<?php

namespace App\Resources\Healthcare\Methods;


use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\RestListener;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use DB;

class AgeGroups extends HealthcareAbstractRequest
{
    public function executeFunction()
    {
        $ages = DB::connection('mysql_product')
                  ->select('
          SELECT DISTINCT age_from AS age FROM premium_healthcare
            UNION
          SELECT DISTINCT age_to AS age  FROM premium_healthcare
          ORDER BY age
          ');

        $age_groups = array_map(
            function ($age_from, $age_to) {
                return [
                    'age_from' => $age_from->age,
                    'age_to'   => $age_to->age,
                ];
            },
            array_slice($ages, 0, -1),
            array_slice($ages, 1)
        );

        $this->result = $age_groups;
    }
}