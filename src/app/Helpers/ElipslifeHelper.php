<?php


namespace App\Helpers;

use App\Interfaces\ResourceInterface;

class ElipslifeHelper
{
    /**
     * @param $gender
     * @param $smoker
     * @return array
     */
    public static function getBmiListingTypes($gender, $smoker)
    {
        $types = [];

        //Add all possible types:
        if(strtolower($gender)==='female')
            $types[] = ResourceInterface::ELIPSLIFE_NORMAL_FEMALE_APPLY;

        if(!$smoker)
            $types[] = ResourceInterface::ELIPSLIFE_NORMAL_NONSMOKER_APPLY;

        if($smoker)
            $types[] = ResourceInterface::ELIPSLIFE_NORMAL_APPLY;

        return $types;
    }


    /**
     * Returns a given height in CM,
     * in case of Meter input we expect decimal(s).
     * Ie;
     *  1.70 => 170
     *  170  => 170
     * @param $height Height in CM [without decimal(s)] and in Meters with decimal(s).
     * @return int
     */
    public static function processHeightToCM($height)
    {
        return intval((!strpos($height, '.')) ? $height : str_replace('.','', $height));
    }
}