<?php

namespace App\Listeners\Resources2;

use App\Exception\ResourceError;
use App\Exception\ServiceError;
use App\Helpers\HCCHHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use DateTime;
use Input;

/**
 * Class CarinsuranceListener
 * @package App\Listeners\Resources2
 */
class HealthcarechListener
{
    // HACK Translations
    private static $tarif = ['BASE' => 'Freie Arztwahl', 'HAM' => 'Hausarzt', 'HMO' => 'HMO', 'DIV' => 'TelMed'];
    private static $accident = ['0' => 'Nein', '1' => 'Ja'];

    private static $hmoMapping = [];

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.product.healthcarech.process.input', [$this, 'getRegionKantonFromPostalcode']);
        $events->listen('resource.product.healthcarech.process.input', [$this, 'getMapHMO']);
        $events->listen('resource.product.healthcarech.process.input', [$this, 'birthdateToAgeGroup']);
        $events->listen('resource.product.healthcarech.process.input', [$this, 'birthyearToAgeGroup']);
        $events->listen('resource.contract.healthcarech.process.input', [$this, 'contractInputs']);
        $events->listen('resource.product.healthcarech.process.after', [$this, 'after']);

        //keep this order
        $events->listen('resource.product.healthcarech.order.before', [$this, 'filterHam']);
        $events->listen('resource.product.healthcarech.limit.before', [$this, 'filterActive']);
        $events->listen('resource.product.healthcarech.limit.before', [$this, 'filterHmos']);
        $events->listen('resource.product.healthcarech.limit.before', [$this, 'filterAltersuntergruppe']);
        $events->listen('resource.product.healthcarech.limit.before', [$this, 'bestQualityPriceEnabled']);


        $events->listen('resource.models.company.process.input', [$this, 'companyDefaultOrder']);
        $events->listen('resource.models.company.process.after', [$this, 'prepareDropdown']);

        //VVG Products
        //$events->listen('resource.vvgproducts_product.healthcarech.process.input', [$this, 'processVVGInput']);
    }


    /**
     * Ham should only display most expensive ones
     *
     */
    public static function filterHam(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $isHam = (isset($input['kanton'], $input['region'], $input['tarif_type']) && ($input['tarif_type'] == 'HAM'));
        if( ! $isHam){
            return;
        }

        $hamArr = [];
        foreach($output as $row){
            if( ! isset($row['company'])){
                continue;
            }

            if( ! isset($hamArr[$row['company']['__id']])){
                $hamArr[$row['company']['__id']] = $row;
                continue;
            }
            if (((float) $row[ResourceInterface::PRICE]) > ((float)$hamArr[$row['company']['__id']][ResourceInterface::PRICE])) {
                $hamArr[$row['company']['__id']] = $row;
            }
        }

        $output->exchangeArray(array_values($hamArr));
    }

    /**
     * Add flags for best price and service
     *
     */
    public static function filterAltersuntergruppe(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $result = [];
        foreach($output as $row){
            if( isset($row['altersuntergruppe']) && in_array($row['altersuntergruppe'],['K2','K3']) ){
                continue;
            }
            $result[] = $row;
        }
        $output->exchangeArray($result);
    }
    public static function filterHmos(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $isHmo = (isset($input['kanton'], $input['region'], $input['tarif_type']) && ($input['tarif_type'] == 'HMO'));

        $result = [];
        /**
         *  Filter out non valid HMO's.
         */
        foreach($output as $row){
            if( ! isset($row['company'])){
                continue;
            }
            if($isHmo){
                if( ! isset(self::$hmoMapping[$row['company']['__id']]) || ! (in_array($row['tarif'], self::$hmoMapping[$row['company']['__id']]))){
                    //fucked up exception for sanitas
                    //run this query:
                    //db.getCollection('product').update({_type:"healthcarech", company: 1509, tarif: "NetMed_10"},{$set:{tarif_type:"HMO"}},{multi: true})
                    if($row['company']['__id'] != 1509){
                        continue;
                    }
                }
            }
            $result[] = $row;
        }

        $output->exchangeArray($result);
    }

    public static function filterActive(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $rows = [];
        foreach($output->getArrayCopy() as $row){
            if( ! isset($row['company']['active'])){
                continue;
            }
            if($row['company']['active'] == true){
                $rows [] = $row;
            }
        }
        $output->exchangeArray($rows);
    }
    /**
     * Add flags for best price and service
     *
     */
    public static function bestQualityPriceEnabled(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $rows = $output->getArrayCopy();
        $ratingIndex = 0;
        $rating      = 0;
        $cheapIndex  = 0;
        $cheap       = 999999;
        foreach($rows as $index => $row){
            if($row['price'] < $cheap){
                $cheapIndex = $index;
                $cheap      = $row['price'];
            }
            if($row['company']['gesat_rating'] > $rating){
                $ratingIndex = $index;
                $rating      = $row['company']['gesat_rating'];
            }
        }
        $rows[$cheapIndex]['best_price']    = true;
        $rows[$ratingIndex]['best_service'] = true;
        $output->exchangeArray($rows);
    }

    /**
     * Set default order to title
     */
    public static function companyDefaultOrder(Resource $resource, ArrayObject $input)
    {
        $inputArr           = $input->getArrayCopy();
        $inputArr["_order"] = 'title';
        $input->exchangeArray($inputArr);
    }

    public static function after(Resource $resource, ArrayObject $input, ArrayObject $output)
    {

        $result = [];

        /**
         * Add ratings, adress, etc
         */
        foreach($output as $row){
            if( ! isset($row['company'])){
                continue;
            }

            //later...
//            $row['price_default']  = $row['price'];
//            $row['price'] = ($row['price'] * 100 - 565) / 100;
//            $row['price_discount'] = 5.65;

            $row['rating']  = $row['company']['gesat_rating'];
            $row['address'] = array_map('trim', explode(PHP_EOL, $row['company']['address']));
            $row['pros']    = array_map('trim', explode(PHP_EOL, $row['company']['positive']));
            $row['cons']    = array_map('trim', explode(PHP_EOL, $row['company']['negative']));
            // HACK, Should be handled with translations / get value from json
            $row['tarif_type'] = self::$tarif[$row['tarif_type']];
            // HACK, Should be handled with translations / get value from json
            // if you remove this, also remove the unhack at L331
            $row['accident'] = self::$accident[$row['accident']];
            $result[]        = $row;
        }
        $output->exchangeArray($result);
    }

    public static function prepareDropdown(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(isset($input['_nodropdown']) && $input['_nodropdown']){
            return;
        }
        //nasty! we only want this for direct calls and not for internal calls;
        $outputArr      = $output->getArrayCopy();
        $row['knip_id'] = -1;
        $row['title']   = "";
        array_unshift($outputArr, $row);
        $row['knip_id'] = 0;
        $row['title']   = "Ich habe aktuell keine Grundversicherung";
        array_unshift($outputArr, $row);
        $row['knip_id'] = -1;
        $row['title']   = "Bitte wählen";
        array_unshift($outputArr, $row);

        foreach($outputArr as &$row){
            $row['name']  = $row['knip_id'];
            $row['label'] = $row['title'];
        }
        $output->exchangeArray($outputArr);
    }


    /**
     * Map postal code to kanton
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function getRegionKantonFromPostalcode(Resource $resource, ArrayObject $input)
    {
        if( ! isset($input[ResourceInterface::POSTAL_CODE])){
            return;
        }

        cws('getRegionKantonFromPostalcode');

        //get region resource
        $resource = Resource::where('name', 'models.region')->firstOrFail();

        $result = ResourceHelper::call($resource, 'index', [ResourceInterface::POSTAL_CODE => $input[ResourceInterface::POSTAL_CODE]]);


        cwe('getRegionKantonFromPostalcode');
        if( ! count($result)){
            return;
        }


        if (isset($input['commune_id'])) {
            $resourceCommune = Resource::where('name', 'models.commune')->firstOrFail();
            $resultCommunes = ResourceHelper::call($resourceCommune, 'index', [ResourceInterface::POSTAL_CODE => $input[ResourceInterface::POSTAL_CODE]]);
            foreach($resultCommunes as $resultCommune) {
                if ($input['commune_id'] == $resultCommune['name']) {
                    $input->offsetUnset(ResourceInterface::POSTAL_CODE);
                    $input->offsetSet('kanton', $resultCommune['kanton']);
                    $input->offsetSet('region', $resultCommune['region']);
                    return;
                }
            }
        }


        /**
         * Remove postcode, hello kanton/region
         */
        $input->offsetUnset(ResourceInterface::POSTAL_CODE);
        $input->offsetSet('kanton', $result[0]['kanton']);
        $input->offsetSet('region', $result[0]['region']);

    }

    public static function getMapHMO(Resource $resource, ArrayObject $input)
    {

        /**
         * Map HMO
         */
        if( ! (isset($input['kanton'], $input['region'], $input['tarif_type']) && ($input['tarif_type'] == 'HMO'))){
            return;
        }

        //get region resource
        $resource = Resource::where('name', 'models.hmo')->firstOrFail();
        $hmoRes   = ResourceHelper::call($resource, 'index', ['kanton' => $input['kanton'], 'region' => $input['region'], OptionsListener::OPTION_VISIBLE => 'company_id,hmo']);
        foreach($hmoRes as $res){
            if( ! isset(self::$hmoMapping[$res['company_id']])){
                self::$hmoMapping[$res['company_id']] = [];
            }
            if(in_array($res['hmo'], self::$hmoMapping[$res['company_id']])){
                continue;
            }
            self::$hmoMapping[$res['company_id']][] = $res['hmo'];
        }
    }


    public static function birthyearToAgeGroup(Resource $resource, ArrayObject $input)
    {
        if( ! isset($input['birthyear']) or ! is_numeric($input['birthyear'])){
            return;
        }

        $year = date('Y') - $input['birthyear'];
        $age  = HCCHHelper::yearsToGroup($year);

        $input->offsetSet('age', $age);
        $input->offsetUnset('birthyear');
    }

    public static function birthdateToAgeGroup(Resource $resource, ArrayObject $input)
    {
        if( ! isset($input['birthdate'])){
            return;
        }

        $birthdate = new DateTime($input['birthdate']);
        $today     = new DateTime();
        $interval  = $today->diff($birthdate);
        $years     = (int) $interval->format('%y');

        $age = HCCHHelper::yearsToGroup($years);

        $input->offsetUnset('birthdate');
        $input->offsetSet('age', $age);
    }


    public static function contractInputs(Resource $resource, ArrayObject $input, $action)
    {
        $inputArr                                     = $input->getArrayCopy();
        if (!isset($inputArr[ResourceInterface::PHONE])) {
            $input->offsetSet(OptionsListener::OPTION_BYPASS, true);
            $input->offsetSet(OptionsListener::OPTION_SKIP_VALIDATE, true);
            return;
        }

        if($action != 'store'){
            $input->offsetSet(OptionsListener::OPTION_SKIP_VALIDATE, true);
            return;
        }
        $productId = $input[ResourceInterface::PRODUCT_ID];

        $resource   = Resource::where('name', 'product.healthcarech')->firstOrFail();
        $productArr = ResourceHelper::call($resource, 'index', ['__id' => $productId]);
        if( ! count($productArr)){
            throw new ServiceError($resource, $input->getArrayCopy(), 'Product not found: ' . $productId);
        }

        $product                                      = $productArr[0];

        $inputArr["kanton"]                           = $product["kanton"];
        $inputArr["region"]                           = $product["region"];

        // UNHACK, unfuck the translation above
        $inputArr["accident"]                         = ($product["accident"] == 'Ja')?"1":"0";

        $inputArr["franchise"]                        = $product["franchise"];
        $inputArr["company"]                          = $product["franchise"];
        $inputArr[ResourceInterface::MODEL_ID]        = $product["tarif_type"];
        $inputArr[ResourceInterface::PHONE]           = '+41' . $inputArr[ResourceInterface::PHONE];
        $inputArr[ResourceInterface::NEW_PROVIDER_ID] = $product['company']['knip_id'];


        /**
         * Check BAG ID
         */
        $resource = Resource::where('name', 'models.company')->firstOrFail();
        if(isset($inputArr[ResourceInterface::BAG_ID]) && $inputArr[ResourceInterface::BAG_ID] > 0){

            $result = ResourceHelper::call($resource, 'index', ['__id' => $inputArr[ResourceInterface::BAG_ID], '_nodropdown' => true, OptionsListener::OPTION_VISIBLE => '__id,knip_id']);
            if( ! count($result)){
                throw new ResourceError($resource, $inputArr, [
                    [
                        "code"    => 'healthcarech.error.bag_id',
                        "message" => 'Geschäft nicht gefunden',
                        "field"   => ResourceInterface::BAG_ID,
                        'type'    => 'input'
                    ]
                ]);
            }
            $inputArr[ResourceInterface::CURRENT_PROVIDER_ID] = $result[0]['knip_id'];
        }else{
            $result = ResourceHelper::call($resource, 'index', ['knip_id' => $inputArr[ResourceInterface::CURRENT_PROVIDER_ID], '_nodropdown' => true, OptionsListener::OPTION_VISIBLE => '__id,knip_id']);
            if( ! count($result)){
                $inputArr[ResourceInterface::BAG_ID] = 0;
            }else{
                $inputArr[ResourceInterface::BAG_ID] = $result[0]['__id'];
            }
        }
        $inputArr[ResourceInterface::PRICE] = $product['price'];
        if( ! isset($inputArr[ResourceInterface::POLICY_NUMBER])){
            $inputArr[ResourceInterface::POLICY_NUMBER] = 0;
        }

        $inputArr["tenant"] = 'ch';
        $input->exchangeArray($inputArr);
    }


    /*
     * VVG Stuff
     */


    public static function processVVGInput(Resource $resource, ArrayObject $input) {
        $age = self::getAgeFromBirthdate($input[ResourceInterface::BIRTHDATE]);
        $coverages = $input[ResourceInterface::COVERAGE];
        //first we go sequential
    }

    private static function getAgeFromBirthdate($birthDate)
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