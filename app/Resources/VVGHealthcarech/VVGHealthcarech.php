<?php

namespace App\Resources\VVGHealthcarech;


use App\Resources\AbstractServiceRequest;

class VVGHealthcarech extends AbstractServiceRequest
{
    protected $methodMapping = [
        'IndexProduct'  => [
            'class'       => \App\Resources\VVGHealthcarech\Methods\IndexProduct::class,
            'description' => 'List healthcare products',
        ],
        'ShowProduct'   => [
            'class'       => \App\Resources\VVGHealthcarech\Methods\ShowProduct::class,
            'description' => 'Show a single healthcare products',
        ],
        'UpdateProduct' => [
            'class'       => \App\Resources\VVGHealthcarech\Methods\UpdateProduct::class,
            'description' => 'Update a healthcare product',
        ],
        'StoreProduct'  => [
            'class'       => \App\Resources\VVGHealthcarech\Methods\StoreProduct::class,
            'description' => 'Create a new healthcare product',
        ],
        'contract'  => [
            'class'       => \App\Resources\VVGHealthcarech\Methods\Contract::class,
            'description' => 'Create a healthcare contract',
        ],

    ];

    public function getSyncableMethods()
    {
        return array_keys($this->methodMapping);
    }

    public static function getAgeFromBirthdate($birthDate)
    {
        $birthdate = \DateTime::createFromFormat('Y-m-d', $birthDate);

        $curMonth  = date('n');
        $startDate = new \DateTime('first day of next month');
        if ($curMonth >= 8) {
            $startDate = new \DateTime('first day of next year');
        }

        $age = $birthdate->diff($startDate)->y;

        return $age;
    }
}