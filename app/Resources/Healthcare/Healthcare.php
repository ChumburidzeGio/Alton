<?php

namespace App\Resources\Healthcare;


use App\Resources\AbstractServiceRequest;

class Healthcare extends AbstractServiceRequest
{
    const PREMIUM_GROUP_ID_ZORGWEB = 1;
    const PREMIUM_GROUP_ID_ZORGWEB_COLLECTIVITY = 2;
    const PREMIUM_GROUP_ID_ZORGWEB_EXTRA_COLLECTIVITY = 3;
    const PREMIUM_GROUP_ID_KOMPARU_AFFILIATE = 100;
    const PREMIUM_GROUP_ID_IAK_COLLECTIVITY_IAK_START = 90000;
    const PREMIUM_GROUP_ID_IAK_COLLECTIVITY_IAK_STOP = 99999;
    const PREMIUM_GROUP_ID_IAK_COLLECTIVITY_ZORGWEB_START = 400000;
    const PREMIUM_GROUP_ID_IAK_COLLECTIVITY_ZORGWEB_STOP = 409999;

    protected $methodMapping = [
        'IndexProduct'  => [
            'class'       => \App\Resources\Healthcare\Methods\IndexProduct::class,
            'description' => 'List healthcare products',
        ],
        'ShowProduct'   => [
            'class'       => \App\Resources\Healthcare\Methods\ShowProduct::class,
            'description' => 'Show a single healthcare products',
        ],
        'UpdateProduct' => [
            'class'       => \App\Resources\Healthcare\Methods\UpdateProduct::class,
            'description' => 'Update a healthcare product',
        ],
        'StoreProduct'  => [
            'class'       => \App\Resources\Healthcare\Methods\StoreProduct::class,
            'description' => 'Create a new healthcare product',
        ],

        // this relates to the other resouce2's
        'age_groups'    => [
            'class'       => \App\Resources\Healthcare\Methods\AgeGroups::class,
            'description' => 'Gets age groups from premiums',
        ],
        'product_details'    => [
            'class'       => \App\Resources\Healthcare\Methods\ProductDetails::class,
            'description' => 'Gets age groups from premiums',
        ],
        'cart'    => [
            'class'       => \App\Resources\Healthcare\Methods\Cart::class,
            'description' => 'Return a list of ordered products and a total',
        ],
        'composer'    => [
            'class'       => \App\Resources\Healthcare\Methods\Composer::class,
            'description' => 'Composer with the prices',
        ],
        'sidebyside'    => [
            'class'       => \App\Resources\Healthcare\Methods\SideBySide::class,
            'description' => 'Data for side by side comparison of products',
        ],
        'form'    => [
            'class'       => \App\Resources\Healthcare\Methods\Form::class,
            'description' => 'Healthcare Zorgweb formulier service',
        ],
        'premium_structure'    => [
            'class'       => \App\Resources\Healthcare\Methods\PremiumStructure::class,
            'description' => 'Healthcare Zorgweb premium structure description service',
        ],
        'contract'    => [
            'class'       => \App\Resources\Healthcare\Methods\Contract::class,
            'description' => 'Healthcare Zorgweb contract',
        ],
        'submit_form'    => [
            'class'       => \App\Resources\Healthcare\Methods\SubmitForm::class,
            'description' => 'Healthcare Zorgweb submit form questions',
        ],
        'coverage_summary'    => [
            'class'       => \App\Resources\Healthcare\Methods\CoverageSummary::class,
            'description' => 'Healthcare Coverage Summary',
        ],
        'websites_daisycon'    => [
            'class'       => \App\Resources\Healthcare\Methods\WebsitesDaisycon::class,
            'description' => 'Daisycon website generation service',
        ]
    ];

    public function getSyncableMethods()
    {
        return array_keys($this->methodMapping);
    }

    public static function getAgeFromBirthdate($birthDateString)
    {
        $birthdate = \DateTime::createFromFormat('Y-m-d', $birthDateString);

        if (!$birthdate)
            $birthdate = new \DateTime($birthDateString);

        if (!$birthdate)
            return 0;

        $curMonth = (int) date('n');
        $curYear  = (int) date('Y');
        if($curMonth >= 11){
            $firstDayNextMonth = mktime(0, 0, 0, 1, 1, $curYear + 1);
        }else{
            $firstDayNextMonth = mktime(0, 0, 0, $curMonth + 1, 1);
        }
        $startDate = new \DateTime(date('Y-m-d', $firstDayNextMonth));

        $age = $birthdate->diff($startDate)->y;

        return $age;
    }
}