<?php


namespace App\Helpers;

use App\Interfaces\ResourceInterface;
use Str;
use TijsVerkoyen\CssToInlineStyles\Exception;

class Healthcare2018Helper
{

    const BASE_OWN_RISK = 385;

    const COVERAGE_ORDER = [
        'base',
        'additional',
        'fysio',
        'tand',
        'buitenland',
        'alternatief',
        'ortho',
        'gezinsplanning',
        'klasse',
        'therapie'
    ];

    const PERSONS = ['applicant', 'applicant_partner', 'child0', 'child1', 'child2', 'child3', 'child4', 'child5', 'child6', 'child7', 'child8', 'child9'];
    const MEMBER_MAPPING = [
        'applicant'         => 'aanvrager',
        'applicant_partner' => 'partner',
        'child0'            => 'kinderen.0',
        'child1'            => 'kinderen.1',
        'child2'            => 'kinderen.2',
        'child3'            => 'kinderen.3',
        'child4'            => 'kinderen.4',
        'child5'            => 'kinderen.5',
        'child6'            => 'kinderen.6',
        'child7'            => 'kinderen.7',
        'child8'            => 'kinderen.8',
        'child9'            => 'kinderen.9',
    ];

    /**
     * @param \stdClass $coverage
     *
     * @return string[]
     */
    static function getCoverageName($coverage)
    {
        $slug = Str::slug($coverage->name, '_');
        $name = substr($coverage->__id . '_' . $slug, 0, 62);

        return [$name, $name . '_p'];
    }

    static function sortAdditionalCoverages($coverages)
    {
        usort($coverages, function ($c1, $c2) {
            return array_search($c1['type'], self::COVERAGE_ORDER) - array_search($c2['type'], self::COVERAGE_ORDER);
        });

        return $coverages;
    }

    static function getProductStructure($productId, $ownRisk, $birthdate, $user, $website, $collectivityId = 0, $otherParams = [])
    {
        $productParams = [
            'product_id'      => $productId,
            'own_risk'        => $ownRisk,
            'birthdate'       => $birthdate,
            'website'         => $website,
            'user'            => $user,
            'collectivity_id' => $collectivityId,
        ];

        $productParams = array_merge($productParams, $otherParams);
        $product       = ResourceHelper::callResource2('product.healthcare2018', $productParams);
        $extended      = ResourceHelper::callResource2('premium_structure.healthcare2018', $productParams);
        if(empty($product)){
            return null;
        }
        $product = head($product);
        return ['total_product' => $product, 'structure' => $extended];
    }

    public static function sessionPrakker($sessionData)
    {
        $return = [];
        foreach($sessionData as $page => $sessionArray){
            foreach($sessionArray as $resourceName => $resourceMap){
                if( ! is_array($resourceMap)){
                    continue;
                }
                $resourceMap = array_dot($resourceMap);
                foreach($resourceMap as $key => $val){
                    if( ! isset($return[$key])){
                        $return[$key] = $val;
                    }
                }
            }
        }
        return $return;

    }

    /**
     * Check if there is a person in the parameters that is already insured
     *
     * @param $params
     * @param $personName
     *
     * @return bool
     */
    public static function isCurrentlyInsured($params, $personName)
    {
        cw('checking ' . $personName);
        if(array_has($params, $personName . '.insure_with')){
            $insuredWith = array_get($params, $personName . '.insure_with');
            cw('insured with ' . $insuredWith);
            return array_get($params, $insuredWith . '.' . ResourceInterface::CURRENTLY_INSURED, false);
        }
        cw('no insured with ' );
        if(array_get($params, $personName . '.' . ResourceInterface::CURRENTLY_INSURED, false) == true){
            return true;
        }
        return false;

    }

    /**
     * Check if there is a person in the parameters that has a Zorgweb product and is not currently insured
     *
     * @param $params
     * @param $personName
     *
     * @return bool
     */
    public static function shouldGoToZorgweb($params, $personName)
    {
        if( ! self::isCurrentlyInsured($params, $personName)){
            if(substr(array_get($params, $personName . '.' . ResourceInterface::PRODUCT_ID, ''), 0, 1) === 'H'){
                return true;
            }
        }
        return false;
    }

    /**
     * Go through a given parameter array and try to remove persons that should not go to zorgweb.
     * These are either already insured or they have selected a non-zorgweb product
     *
     * @param $params
     *
     * @return mixed
     */
    public static function removeNonZorgweb($params)
    {
        //Initialize cleaned params before cleaning
        $cleanedParams = $params;
        $movedPersons  = [];


        //Go through the persons to clean the array
        foreach(self::PERSONS as $personName){
            if( ! array_get($params, $personName . '.' . ResourceInterface::PRODUCT_ID)){
                continue;
            }

            if( ! self::shouldGoToZorgweb($params, $personName)){
                if($personName === 'applicant'){
                    //The applicant is not going to zorgweb
                    if(self::shouldGoToZorgweb($params, 'applicant_partner')){
                        //However the partner is going to zorgweb so we need to move him up
                        $cleanedParams['applicant'] = $cleanedParams['applicant_partner'];
                        unset($cleanedParams['applicant_partner']);

                        //Keep track of the movements you made so we can move children if necessary
                        $movedPersons['applicant_partner'] = 'applicant';
                    }else{
                        //The partner is also not going to zorgweb so just remove the person
                        unset($cleanedParams[$personName]);
                    }
                }else{
                    //This is the normal case where somebody other than an applicant should not go to zorgweb.
                    //Just unset the corresponding $cleanedParams entry
                    unset($cleanedParams[$personName]);
                }
            }else{
                //The person should go to Zorgweb but first check for insured with.
                //If it is set then this is a child under 18.
                $insuredWith = array_get($params, $personName . '.insured_with', '');
                if($insuredWith !== null){
                    //If the person was moved then move the insured_with to the moved name
                    if(isset($movedPersons[$personName])){
                        array_set($cleanedParams, $personName . '.insured_with', $movedPersons[$personName]);
                    }
                }
            }
        }

        return $cleanedParams;
    }
}