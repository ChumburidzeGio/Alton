<?php

namespace App\Listeners\Resources2;

use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Interfaces\ResourceValue;
use App\Interfaces\ValidatorInterface;
use App\Models\Resource;
use ArrayObject;
use Komparu\Value\ValueInterface;

class InsurancepackageListener
{
    const insurances = [
        'legalexpensesinsurance',
        'homeinsurance',
        'contentsinsurance',
        'liabilityinsurance'
    ];

    const defaultCoverages = 'legalexpensesinsurance.consumer,legalexpensesinsurance.housing';
    const APPARTMENT_REMARK = "Appartementen worden meestal verzekerd via de Vereniging van Eigenaren. In dat geval hoeft u dus alleen uw inboedel te verzekeren.";
    const HOUSE_TYPE_APPARTMENT = 16;
    const coverageTypes = [ResourceValue::EXTENDED, ResourceValue::BASE];

    private $rollsMVFamilyMap = [
        1  => ResourceValue::SINGLE_NO_KIDS,
        10 => ResourceValue::SINGLE_WITH_KIDS,
        7  => ResourceValue::FAMILY_NO_KIDS,
        8  => ResourceValue::FAMILY_WITH_KIDS
    ];
    const ROLLS_PERCENTAGE = 100 / 107;

    private $useContentsValueMeasurement = false;

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.premium_extended.legalexpensesinsurance.process.input', [$this, 'setDefaultsLegalexpenses']);

        $events->listen('resource.premium.liabilityinsurance.rolls.process.after', [$this, 'substractRolsspercentage']);
        $events->listen('resource.product.insurancepackage.process.input', [$this, 'setDefaults']);
        $events->listen('resource.product.insurancepackage.process.input', [$this, 'setHouseOwnerHome']);
        $events->listen('resource.product.insurancepackage.process.input', [$this, 'setUseContentsValueMeasurement']);
        $events->listen('resource.product.insurancepackage.process.input', [$this, 'convertRollsToMoneyview']);
        $events->listen('resource.product.insurancepackage.process.input', [$this, 'setValues']);
        $events->listen('resource.product.insurancepackage.process.after', [$this, 'processBaseExtended']);
        $events->listen('resource.product.insurancepackage.process.after', [$this, 'addCoverages']);
        $events->listen('resource.product.insurancepackage.process.after', [$this, 'groupPackages']);
    }

    /**
     * Moneyview has a different mapping than Rolls. Convert the input
     */
    public function convertRollsToMoneyview(Resource $resource, ArrayObject $input)
    {
        $inputArr = $input->getArrayCopy();
        if(isset($inputArr[ResourceInterface::FAMILY_COMPOSITION])){
            $inputArr[ResourceInterface::PERSON_SINGLE] = $this->rollsMVFamilyMap[$inputArr[ResourceInterface::FAMILY_COMPOSITION]];
            $input->exchangeArray($inputArr);
        }
    }

    /**
     * Set
     */
    public function setValues(Resource $resource, ArrayObject $input)
    {
        if(( ! $input->offsetExists(ResourceInterface::CONTENTS_ESTIMATE)) || ( ! $input->offsetExists(ResourceInterface::CONTENTS_ESTIMATE_VALUE))

        ){
            return;
        }
        if(($input->offsetGet(ResourceInterface::CONTENTS_ESTIMATE) != 200000) || ($input->offsetGet(ResourceInterface::CONTENTS_ESTIMATE_VALUE) < 150000)

        ){
            return;
        }
        cw('Contents set specific, overwrite');
        $input->offsetSet(ResourceInterface::CONTENTS_ESTIMATE, $input->offsetGet(ResourceInterface::CONTENTS_ESTIMATE_VALUE));
    }

    /**
     * Set so called InboedelWaardemeter
     */
    public function setUseContentsValueMeasurement(Resource $resource, ArrayObject $input)
    {
        if(( ! $input->offsetExists(ResourceInterface::MONTHLY_NET_INCOME)) || ($input->offsetGet(ResourceInterface::MONTHLY_NET_INCOME) >= 5500)){
            return;
        }
        cw('Use InboedelWaardemeter');
        $this->useContentsValueMeasurement = true;
        $input->offsetSet(ResourceInterface::USE_CONTENTS_VALUE_MEASUREMENT, true);
    }

    /**
     * Set default values of this resource
     */
    public function setHouseOwnerHome(Resource $resource, ArrayObject $input)
    {
        if(( ! $input->offsetExists(ResourceInterface::HOUSE_OWNER)) || ($input->offsetGet(ResourceInterface::HOUSE_OWNER))){
            return;
        }
        cw('no house owner, remove homeinsurance');
        $input->offsetSet(ResourceInterface::PRODUCT_TYPE, implode(",", array_diff(self::insurances, ['homeinsurance'])));
    }

    /**
     * Set default values of this resource
     */
    public function setDefaults(Resource $resource, ArrayObject $input)
    {
        $inputArr = $input->getArrayCopy();
        //hack to make sure we won't break other legal expenses product
        $productTypeInitialized                          = isset($inputArr[ResourceInterface::PRODUCT_TYPE]);
        $inputArr['_' . ResourceInterface::PRODUCT_TYPE] = $productTypeInitialized ? $inputArr[ResourceInterface::PRODUCT_TYPE] : implode(",", self::insurances);

        //avoid passing cache cause of this
        $inputArr['_' . ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT] = isset($inputArr[ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT]) ? $inputArr[ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT] : 1;
        $inputArr[ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT]       = 1;


        //deselect coverages if product type not there
        if(isset($inputArr[ResourceInterface::SELECTED_COVERAGES])){
            $productTypeArr           = explode(',', $inputArr['_' . ResourceInterface::PRODUCT_TYPE]);
            $selectedCoverageFiltered = [];
            foreach(explode(',', $inputArr[ResourceInterface::SELECTED_COVERAGES]) as $selectedCoverage){
                $prodType = explode('.', $selectedCoverage);
                if(in_array($prodType[0], $productTypeArr)){
                    $selectedCoverageFiltered[] = $selectedCoverage;
                }
            }
            $inputArr['_' . ResourceInterface::SELECTED_COVERAGES] = implode(",", $selectedCoverageFiltered) . ',' . self::defaultCoverages;
        }else{
            $inputArr['_' . ResourceInterface::SELECTED_COVERAGES] = self::defaultCoverages;
        }


        $inputArr[ResourceInterface::PRODUCT_TYPE] = implode(",", self::insurances);


        $inputArr[ResourceInterface::SELECTED_COVERAGES] = 'insurancepackage_coverages';
        //do not cache payment periods
        $inputArr['_' . ResourceInterface::PAYMENT_PERIOD] = $inputArr[ResourceInterface::PAYMENT_PERIOD];
        $inputArr[ResourceInterface::PAYMENT_PERIOD]       = 1;
        $inputArr['_' . ResourceInterface::ORDER]          = ResourceInterface::ORDER;

        if(isset($inputArr[ResourceInterface::HOUSE_ABUTMENT]) && $inputArr[ResourceInterface::HOUSE_ABUTMENT] > 6){
            $inputArr[ResourceInterface::HOUSE_ABUTMENT] = 1;
            $inputArr[ResourceInterface::HOUSE_TYPE]     = 16;
        }else{
            $inputArr[ResourceInterface::HOUSE_TYPE] = 2;
        }
        $input->exchangeArray($inputArr);
    }

    /**
     * Set default values of this resource
     */
    public function setDefaultsLegalexpenses(Resource $resource, ArrayObject $input)
    {
        if(isset($input[ResourceInterface::SELECTED_COVERAGES])){
            if(in_array('insurancepackage_coverages', $input[ResourceInterface::SELECTED_COVERAGES])){
                $coverages = ResourceHelper::callResource2('coverage.legalexpensesinsurance', array_only($input->getArrayCopy(), ['website', 'user']) + ['_limit' => ValueInterface::INFINITE, 'enabled' => true, '_order' => ResourceInterface::ORDER]);
                $input->offsetSet(ResourceInterface::SELECTED_COVERAGES, (array_pluck($coverages, 'name')));
            }
        }
    }

    public function substractRolsspercentage(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(isset($input[ResourceInterface::SELECTED_COVERAGES]) && $input[ResourceInterface::SELECTED_COVERAGES] == 'insurancepackage_coverages'){
            $result = [];
            foreach($output as $row){
                if( ! isset($row[ResourceInterface::PRICE_DEFAULT])){
                    continue;
                }
                $row[ResourceInterface::PRICE_DEFAULT] = round($row[ResourceInterface::PRICE_DEFAULT] * self::ROLLS_PERCENTAGE, 2);
                $result[]                              = $row;
            }
            $output->exchangeArray($result);
        }
    }


    /**
     * Merge extendeds together
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function processBaseExtended(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $selectedCoverages = isset($input['_' . ResourceInterface::SELECTED_COVERAGES]) ? explode(",", $input['_' . ResourceInterface::SELECTED_COVERAGES]) : [];
        $return            = [];
        $copyFields        = [ResourceInterface::PRICE_DEFAULT, ResourceInterface::INSURED_AMOUNT];
        foreach($output as $key => $product){
            $found = false;
            foreach(self::coverageTypes as $coverageType){
                if($product[ResourceInterface::TYPE] != $coverageType){
                    continue;
                }
                foreach($copyFields as $copyField){
                    if(isset($product[$copyField])){
                        $return[$product[ResourceInterface::PRODUCT_TYPE]][$coverageType . '_' . $copyField] = $product[$copyField];
                    }
                }
                $isSelecedCoverage = in_array($product[ResourceInterface::PRODUCT_TYPE] . '.' . $coverageType, $selectedCoverages);
                if(($isSelecedCoverage || ($coverageType == ResourceValue::BASE)) && ! $found){
                    $found = true;
                    cw('Overwriting: ' . $product[ResourceInterface::PRODUCT_TYPE] . ' ' . $product[ResourceInterface::TYPE]);
                    $return[$product[ResourceInterface::PRODUCT_TYPE]] = isset($return[$product[ResourceInterface::PRODUCT_TYPE]]) ? array_merge($return[$product[ResourceInterface::PRODUCT_TYPE]], $product) : $product;
                }
            }
        }

        /**
         * Substract prices
         */
        foreach($return as $key => &$product){
            $prevField = null;
            foreach(array_reverse(self::coverageTypes) as $coverageType){
                if( ! isset($product[$coverageType . '_' . ResourceInterface::PRICE_DEFAULT])){
                    continue;
                }
                if( ! $prevField){
                    $prevField = $coverageType;
                    continue;
                }
                $product[$coverageType . '_' . ResourceInterface::PRICE_DEFAULT] -= $product[$prevField . '_' . ResourceInterface::PRICE_DEFAULT];
            }
        }
        $output->exchangeArray(array_values($return));
    }

    public static function addCoverages(Resource $resource, ArrayObject $input, ArrayObject $output)
    {

        $selectedCoverages = isset($input['_' . ResourceInterface::SELECTED_COVERAGES]) ? explode(",", $input['_' . ResourceInterface::SELECTED_COVERAGES]) : [];

        foreach($output as $key => $product){
            foreach(self::insurances as $insuranceName){
                if($product[ResourceInterface::PRODUCT_TYPE] != $insuranceName){
                    continue;
                }


                /**
                 * Product hacks
                 */
                if($product[ResourceInterface::PRODUCT_TYPE] == 'homeinsurance' && $input->offsetExists(ResourceInterface::HOUSE_TYPE) && ($input->offsetGet(ResourceInterface::HOUSE_TYPE) == self::HOUSE_TYPE_APPARTMENT)){
                    cw('Apparment: REMARK');
                    $output[$key][ResourceInterface::REMARK] = self::APPARTMENT_REMARK;
                }

                /**
                 * Till here
                 */


                $coverages = ResourceHelper::callResource2('coverage.' . $insuranceName,
                    array_only($input->getArrayCopy(), ['website', 'user']) + ['product' => $product[ResourceInterface::__ID], '_limit' => ValueInterface::INFINITE, 'enabled' => true, '_order' => ResourceInterface::ORDER]);


                $coverageRet = [];

                /*
                 * Handle payment period
                 */
                $paymentPeriod                                  = $input->offsetGet('_' . ResourceInterface::PAYMENT_PERIOD);
                $output[$key][ResourceInterface::PRICE_DEFAULT] = $output[$key][ResourceInterface::PRICE_DEFAULT] * $paymentPeriod;
                $output[$key][ResourceInterface::PRICE_ACTUAL]  = $output[$key][ResourceInterface::PRICE_DEFAULT];
                if($product[ResourceInterface::PRODUCT_TYPE] == 'legalexpensesinsurance'){
                    $output[$key][ResourceInterface::PRICE_ACTUAL] = $output[$key][ResourceInterface::PRICE_COVERAGE_SUB_TOTAL] * $paymentPeriod;
                }

                foreach($coverages as $coverage){

                    //price field mapping
                    if($insuranceName == 'legalexpensesinsurance'){
                        $coveragePriceField = 'price_insure_' . $coverage['name'];
                    }else if(in_array($coverage['name'], self::coverageTypes)){
                        $coveragePriceField = $coverage['name'] . '_' . ResourceInterface::PRICE_DEFAULT;
                    }else{
                        $coveragePriceField = 'coverage_' . $coverage['name'] . '_value';
                    }

                    //process label
                    $coverage[ResourceInterface::COVERAGE_LABEL] = self::processLabel($coverage[ResourceInterface::COVERAGE_LABEL], $product);


                    $coverageType = $coverage['name'];

                    //suffix with productup
                    $coverage['name'] = $insuranceName . '.' . $coverage['name'];

                    $coverage['price'] = $paymentPeriod * array_get($product, $coveragePriceField);

                    if( ! $coverage['resource'] && $coverage['price'] == null){
                        unset($coverage);
                        continue;
                    }


                    /**
                     * Extra coverage hacks
                     */
                    //Glasverzekering hier alleen tonen indien 'geen eigen woning'
                    if($input->offsetGet(ResourceInterface::HOUSE_OWNER) && $coverage['name'] == 'contentsinsurance.glass'){
                        unset($coverage);
                        continue;
                    }

                    if(in_array('homeinsurance.extended', $selectedCoverages) && $coverage['name'] == 'homeinsurance.glass'){
                        unset($coverage);
                        continue;
                    }


                    //divorce only for families :)
                    if(( ! in_array($input->offsetGet(ResourceInterface::FAMILY_COMPOSITION), [7, 8])) && $coverage['name'] == 'legalexpensesinsurance.divorce_mediation'){
                        $output[$key][ResourceInterface::PRICE_ACTUAL] -= $coverage['price'];
                        unset($coverage);
                        continue;
                    }
                    /**
                     * Till here
                     */
                    $coverage['is_selected']  = in_array($coverage['name'], $selectedCoverages) || $coverageType == ResourceValue::BASE;
                    $coverage['is_available'] = $coverage['price'] !== null;
                    $coverage['is_covered']   = $coverage['is_selected'] && $coverage['is_available'];
                    if($coverage['resource']){
                        $coverage['choices'] = ResourceHelper::callResource2($coverage[ResourceInterface::RESOURCE],
                            array_only($input->getArrayCopy(), ['website', 'user']) + ['product' => $product[ResourceInterface::__ID], '_order' => ResourceInterface::ORDER, 'enabled' => true]);

                        /**
                         * Set defaults
                         */
                        $selectedName = null;
                        if(isset($input[$coverage[ResourceInterface::RESOURCE_KEY]])){
                            $selectedName = $input[$coverage[ResourceInterface::RESOURCE_KEY]];
                        }

                        if($selectedName !== null){
                            foreach($coverage['choices'] as &$choice){
                                if($choice['name'] == $selectedName){
                                    $choice['is_selected'] = true;
                                }
                            }
                        }
                        unset($coverage['price']);
                    }
                    $coverageRet[] = $coverage;


                    if($product[ResourceInterface::PRODUCT_TYPE] == 'legalexpensesinsurance'){
                        if( ! $coverage['is_selected'] && isset($coverage['price'])){
                            $output[$key][ResourceInterface::PRICE_ACTUAL] -= $coverage['price'];
                        }
                        continue;
                    }

                    /**
                     * If selected, at to price
                     */
                    if(isset($coverage['price']) && $coverage['is_selected'] && ( ! in_array($coverageType, self::coverageTypes))){
                        $output[$key][ResourceInterface::PRICE_ACTUAL] += $coverage['price'];
                    }
                }
                $output[$key]['coverages'] = $coverageRet;
                //$output[$key][ResourceInterface::PRICE_ACTUAL] = round($output[$key][ResourceInterface::PRICE_ACTUAL], 2);

            }
        }
    }

    /**
     * Put all in packages
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public function groupPackages(Resource $resource, ArrayObject $input, ArrayObject $output)
    {


        $productTypes = explode(',', $input['_' . ResourceInterface::PRODUCT_TYPE]);
        //we group by company_id for now
        $groupArray = [];
        foreach($output->getArrayCopy() as $row){
            if( ! ($groupId = (array_get($row, ResourceInterface::GROUP_ID)))){
                continue;
            }
            if( ! array_get($row, ResourceInterface::PRICE_ACTUAL, 0)){
                continue;
            }
            $row['is_selected']                                      = in_array($row[ResourceInterface::PRODUCT_TYPE], $productTypes);
            $groupArray[$groupId][ResourceInterface::PACKAGES][]     = $row;
            $groupArray[$groupId][ResourceInterface::PRODUCT_TYPE][] = $row[ResourceInterface::PRODUCT_TYPE];

            /**
             *  Set GLOBAL PRODUCT SETTINGS  (conditions / email)
             */
            foreach(self::insurances as $insuranceName){
                // conditions
                if($row[ResourceInterface::PRODUCT_TYPE] != $insuranceName || ! in_array($row[ResourceInterface::PRODUCT_TYPE], $productTypes)){
                    continue;
                }
                if(isset($groupArray[$groupId][ResourceInterface::POLICY_CONDITIONS])){
                    $groupArray[$groupId][ResourceInterface::POLICY_CONDITIONS] += $row[ResourceInterface::POLICY_CONDITIONS];
                }else{
                    $groupArray[$groupId][ResourceInterface::POLICY_CONDITIONS] = $row[ResourceInterface::POLICY_CONDITIONS];
                }

                // email
                foreach([ResourceInterface::POLIS_EMAIL_TO, ResourceInterface::POLIS_EMAIL_BCC, ResourceInterface::POLIS_EMAIL_SUBJECT] as $polis_item){
                    if( ! isset($groupArray[$groupId][$polis_item]) && isset($row[$polis_item]) && ! empty($row[$polis_item])){
                        $groupArray[$groupId][$polis_item] = $row[$polis_item];
                    }
                }
            }

        }

        foreach($groupArray as $groupId => &$group){
            $groupSettings = array_except(head(ResourceHelper::callResource2('groupsettings.insurancepackage', ['group_id' => $groupId])), ['__id', '__index', '__type']);
            if( ! $groupSettings){
                continue;
            }


            foreach($groupSettings as $key => $value){
                $groupArray[$groupId][$key] = $value;
            }


            $discountGroups = [];

            $groupArray[$groupId][ResourceInterface::PRICE_DEFAULT] = 0;
            foreach($group[ResourceInterface::PACKAGES] as $package){
                if( ! in_array($package[ResourceInterface::PRODUCT_TYPE], $productTypes)){
                    continue;
                }
                $priceActual                                            = array_get($package, ResourceInterface::PRICE_ACTUAL, 0);
                $groupArray[$groupId][ResourceInterface::PRICE_DEFAULT] += $priceActual;
                if( ! in_array($package[ResourceInterface::DISCOUNT_GROUP_ID], $discountGroups) && $priceActual > 0){
                    $discountGroups[] = $package[ResourceInterface::DISCOUNT_GROUP_ID];
                }
            }

            $dicountScript                                                = $groupSettings[ResourceInterface::DISCOUNT_SCRIPT];
            $dicountScript                                                = str_replace("{{discount_groups}}", count($discountGroups), $dicountScript);
            $function                                                     = create_function('$script', sprintf('return %s;', $dicountScript));
            $groupArray[$groupId][ResourceInterface::DISCOUNT_PERCENTAGE] = $function($dicountScript);


            $groupArray[$groupId][ResourceInterface::PRICE_DISCOUNT] = 0;

            $homeinsuranceAmount = false;
            foreach($group[ResourceInterface::PACKAGES] as $package){
                if( ! in_array($package[ResourceInterface::PRODUCT_TYPE], $productTypes)){
                    continue;
                }
                if( ! $package[ResourceInterface::DISCOUNT_APPLY]){
                    continue;
                }
                if( ! array_get($package, ResourceInterface::PRICE_ACTUAL, 0)){
                    continue;
                }
                cw('adding discount ' . $package['product_type']);
                $groupArray[$groupId][ResourceInterface::PRICE_DISCOUNT] -= (array_get($package, ResourceInterface::PRICE_ACTUAL, 0) * ($groupArray[$groupId][ResourceInterface::DISCOUNT_PERCENTAGE] / 100));


                if($package[ResourceInterface::PRODUCT_TYPE] == 'homeinsurance' && isset($package[ResourceInterface::INSURED_AMOUNT])){
                    $homeinsuranceAmount = $package[ResourceInterface::INSURED_AMOUNT];
                }
            }
            //only set incasso costs when we have don't want automatic off writing :)
            $groupArray[$groupId][ResourceInterface::PRICE_MANUAL_BILLING] = ($input['_' . ResourceInterface::PAYMENT_PREAUTHORIZED_DEBIT]) ? 0.0 : $groupArray[$groupId][ResourceInterface::PRICE_MANUAL_BILLING];
            $groupArray[$groupId][ResourceInterface::PRICE_ACTUAL]         = $groupArray[$groupId][ResourceInterface::PRICE_DEFAULT] + $groupArray[$groupId][ResourceInterface::PRICE_DISCOUNT] + $groupSettings[ResourceInterface::PRICE_FEE] + $groupArray[$groupId][ResourceInterface::PRICE_MANUAL_BILLING];
            $groupArray[$groupId][ResourceInterface::PRODUCT_TYPE]         = $input['_' . ResourceInterface::PRODUCT_TYPE];

            //aan als inboedelwaarde meter is gebruikt
            $groupArray[$groupId][ResourceInterface::GUARANTEE_FOR_UNDERINSURANCE_CONTENTS] = $this->useContentsValueMeasurement;
            //aan als infofolio is gebruikt, en het bedrag is niet groter dan 500.000 (opstal bedrag)
            $groupArray[$groupId][ResourceInterface::GUARANTEE_FOR_UNDERINSURANCE_HOME]     = ((isset($input[ResourceInterface::INFOFOLIO]) && $input[ResourceInterface::INFOFOLIO]) && ($homeinsuranceAmount !== false && $homeinsuranceAmount <= 500000));

        }
        $output->exchangeArray(array_values($groupArray));
    }

    //selected_coverages
    //contentsinsurance.outdoors%2Ccontentsinsurance.glass

    /**
     * Helper to replace place holder with value
     *
     * @param $label
     * @param $product
     */
    private static function processLabel($label, $product)
    {
        if( ! $label){
            return "";
        }
        if(preg_match('/{{(.+)}}/', $label, $matches)){
            $fieldArr = explode('|', $matches[1]);
            if( ! array_has($product, head($fieldArr))){
                return str_replace($matches[0], "", $label);
            }
            $value = array_get($product, head($fieldArr));
            if(isset($fieldArr[1]) && $fieldArr[1] == 'price_round'){
                $value = number_format($value, 0, ',', '.');
            }
            if(isset($fieldArr[1]) && $fieldArr[1] == 'price'){
                $value = number_format($value, 2, ',', '.');
            }
            return str_replace($matches[0], $value, $label);
        }
        return $label;

    }
}