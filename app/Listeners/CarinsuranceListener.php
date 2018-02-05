<?php

namespace App\Listeners\Resources2;

use App\Exception\ResourceError;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Helpers\WebsiteHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Komparu\Value\ValueInterface;

/**
 * Class CarinsuranceListener
 * @package App\Listeners\Resources2
 */
class CarinsuranceListener
{
    private static $volmachtUsers = [2125,4261];
    private static $externalOffice = [
        //Independent
        3971 => 10006,
        //IAK
        4261 => 2947,
    ];


    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.product.carinsurance.process.input', [$this, 'validateDamageFreeYearsAndAge']);
        $events->listen('resource.product.carinsurance.process.input', [$this, 'splitCoverageAllForProductCarinsurance']);
        $events->listen('resource.product.carinsurance.process.input', [$this, 'setCarOwnerDetails']);
        $events->listen('resource.product.carinsurance.process.input', [$this, 'removePakketGeen']);
        $events->listen('resource.product.carinsurance.collection.after', [$this, 'addProsConsProductCarinsurance']);
        $events->listen('resource.product.carinsurance.limit.before', [$this, 'filterOutMissingPremiums']);
        $events->listen('resource.product.carinsurance.process.after', [$this, 'addPriceQualityOrder']);
        $events->listen('resource.product.carinsurance.process.after', [$this, 'affiliateOnly']);
        $events->listen('resource.product.carinsurance.process.after', [$this, 'setKomparuVerzekeringenUrl']);
        $events->listen('resource.product.carinsurance.process.after', [$this, 'changeAccessoiresCoverage']);
        $events->listen('resource.product.carinsurance.process.after', [$this, 'checkLancyrNoClaimProtection']);
        //TODO: Enable if lancyr makes it to the new stuff
        //        $events->listen('resource.product.carinsurance.process.after', [$this, 'tweakLancyrConditions']);
        $events->listen('resource.product.carinsurance.process.after', [$this, 'addPaymentPeriodString']);

        $events->listen('resource.product.carinsurance.process.after', [$this, 'changeBusinessPriceInitial']);

        $events->listen('resource.toplist.carinsurance.process.after', [$this, 'addProductData']);

        //$events->listen('resource.category.carinsurance_coverages.process.input', [$this, 'addVolmachtUserCondition']);

        $events->listen('resource.premium.carinsurance.process.input', [$this, 'setExternalOfficeIds']);
    }

    public static function addPaymentPeriodString(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if (empty($output) || !($action === RestListener::ACTION_INDEX || $action === RestListener::ACTION_SHOW)) { return; }

        $paymentPeriod = intval($input['payment_period']);
        $paymentPeriodStr = $paymentPeriod === 1 ? 'maand' : 'jaar';

        foreach($output as $row => &$data){
            $data['payment_period_str'] = $paymentPeriodStr;
        }
    }

    public static function validateDamageFreeYearsAndAge(Resource $resource, ArrayObject $input)
    {
        if (isset($input[ResourceInterface::YEARS_WITHOUT_DAMAGE], $input[ResourceInterface::BIRTHDATE])
            and (date('Y',strtotime($input[ResourceInterface::BIRTHDATE])) + $input[ResourceInterface::YEARS_WITHOUT_DAMAGE] + 17 > date('Y'))
        ) {
            throw new ResourceError($resource, $input->getArrayCopy(), [
                [
                    "code"    => 'carinsurance.error.' . ResourceInterface::YEARS_WITHOUT_DAMAGE,
                    "message" => 'Schadevrije jaren mogen niet hoger zijn dan leeftijd minus 17 jaar',
                    "field"   => ResourceInterface::YEARS_WITHOUT_DAMAGE,
                    "type"    => 'input'
                ]
            ]);
        }
    }

    public static function changeBusinessPriceInitial(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }

        if(!isset($input[ResourceInterface::BUSINESS]))
        {
            return;
        }

        if($resource->name != 'product.carinsurance' || count($output) > 3){
            return;
        }
        foreach($output as $row => $data){
            if($input[ResourceInterface::BUSINESS] == ResourceInterface::TRUE){
                $output[$row][ResourceInterface::PRICE_INITIAL] = 18.15;
            }
        }
    }


    /**
     * Sometime we use other rolls office ID
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function setExternalOfficeIds(Resource $resource, ArrayObject $input) {
        if(!isset($input[ResourceInterface::USER]) || !in_array($input[ResourceInterface::USER],array_keys(self::$externalOffice))) {
            return;
        }
        $input->offsetSet(ResourceInterface::OFFICE_ID, self::$externalOffice[$input[ResourceInterface::USER]]);
    }


    public static function affiliateOnly(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! isset($input['_affiliate_only'])){
            return;
        }
        $result = [];
        foreach($output as $row){
            if(isset($row['url']) && $row['url'] != "#"){
                $result[] = $row;
            }
        }
        $output->exchangeArray($result);

    }

    public function changeAccessoiresCoverage(Resource $resource, ArrayObject $input, ArrayObject $data)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }
        $work = $data->getArrayCopy();

        array_walk($work, function (&$product) {
            if($product['coverage'] === 'wa'){
                $product['accessoires_coverages_enabled'] = false;
            }
        });

        $data->exchangeArray($work);
    }


    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     * @param ArrayObject $resolved
     * @param $action
     * @param $id
     */
    public function addProductData(Resource $resource, ArrayObject $input, ArrayObject $data)
    {

        $productIds         = Arr::pluck($data->getArrayCopy(), 'product_id');
        $options['filters'] = ['__id' => $productIds];
        $options['limit']   = ValueInterface::INFINITE;
        $options['visible'] = 'company.image,company.title,url';
        if(isset($input[ResourceInterface::WEBSITE])){
            $options['conditions'][ResourceInterface::WEBSITE] = $input[ResourceInterface::WEBSITE];
        }
        if(isset($input[ResourceInterface::USER])){
            $options['conditions'][ResourceInterface::USER] = $input[ResourceInterface::USER];
        }
        $products = DocumentHelper::get('product', 'carinsurance', $options)->documents()->toArray();
        $prodKey  = [];
        foreach($products as $product){
            $prodKey[$product['__id']] = $product;
        }

        foreach($data as &$row){
            $prodRow              = $prodKey[$row['product_id']];
            $row['company_image'] = array_get($prodRow, 'company.image');
            $row['company_title'] = array_get($prodRow, 'company.title');
            $row['url']           = array_get($prodRow, 'url');
        }
    }


    public function addProsConsProductCarinsurance(Resource $resource, ArrayObject $input, ArrayObject $output, ArrayObject $resolved, $action, $id)
    {
        foreach($output as &$row){
            //The voorwaarden can be different than the normal pros and cons for display in voorwaarden zones.
            //Iak carinsurance uses this for example.
            if(isset($row['coverage'], $row['policy'], $row['policy'][$row['coverage']],$row['policy'][$row['coverage']]['Voorwaarden'])){
                $voorwaarden = $row['policy'][$row['coverage']]['Voorwaarden'];
                $row['voorwaarden_pros'] = $this->parseFromPolicyHtmlList($voorwaarden, 'Pluspunten');
                $row['voorwaarden_cons'] = $this->parseFromPolicyHtmlList($voorwaarden, 'Minpunten');
            }

            if( ! isset($row['coverage'], $row['policy'], $row['policy'][$row['coverage']], $row['policy'][$row['coverage']]['Plus- en minpunten'])){
                continue;
            }
            $prosAndCons = $row['policy'][$row['coverage']]['Plus- en minpunten'];
            $row['pros'] = $this->parseFromPolicyHtmlList($prosAndCons, 'Pluspunten');
            $row['cons'] = $this->parseFromPolicyHtmlList($prosAndCons, 'Minpunten');
        }
    }


    public static function setCarOwnerDetails(Resource $resource, ArrayObject $input)
    {
        // check if the is_car_owner is set to true, if so use the car_owner details instead of the regular ones
        if(isset($input['is_car_owner']) && $input['is_car_owner'] === 'false'){
            if(isset($input['car_owner_postal_code'])){
                $input->offsetSet('post_code', $input['car_owner_postal_code']);
            }
            if(isset($input['car_owner_birthdate'])){
                $input->offsetSet('birthdate', $input['car_owner_birthdate']);
            }
            if(isset($input['car_owner_years_without_damage'])){
                $input->offsetSet('years_without_damage', $input['car_owner_years_without_damage']);
            }
        }
    }

    public static function removePakketGeen(Resource $resource, ArrayObject $input)
    {
        $inputArr = $input->getArrayCopy();
        // Now we don't need the 'all' value, we can keep it empty
        if(isset($inputArr['accessoires_coverage']) && ($inputArr['accessoires_coverage'] == '0' || $inputArr['accessoires_coverage'] == 'geen')){
            unset($inputArr['accessoires_coverage']);
            $input->exchangeArray($inputArr);
        }
    }

    /**
     * Bit hacky function to fill pros/cons from policy html list
     *
     * @param $array
     * @param $key
     *
     * @return array
     */

    private function parseFromPolicyHtmlList($array, $key)
    {
        if( ! isset($array[$key])){
            return [];
        }
        $content = $array[$key];
        if(strpos($content, '<ul>') === false){
            $onePoint = trim($content);

            return ($onePoint == 'Geen minpunten' ? [] : [$onePoint]);
        }
        $content = str_replace('<ul>', '', $content);
        $content = str_replace('</ul>', '', $content);
        $content = str_replace("\n", '', $content);
        preg_match_all('/<li>([^<>]+)<\/li>/', $content, $matches);
        if(count($matches) < 2){
            return [trim($content)];
        }
        $ret = [];
        foreach($matches[1] as $match){
            $ret[] = trim($match);
        }

        return $ret;
    }

    /**
     * Small helper to allow an empty 'coverage' param instead of always
     * providing 'all'.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     */
    public static function splitCoverageAllForProductCarinsurance(Resource $resource, ArrayObject $input)
    {
        $inputArr = $input->getArrayCopy();
        // Now we don't need the 'all' value, we can keep it empty
        if( ! isset($inputArr['coverage']) || $inputArr['coverage'] == 'all'){
            $inputArr['coverage'] = ['wa', 'bc', 'vc'];
            $input->exchangeArray($inputArr);
        }

    }


    /**
     * Add a price quality order to the output
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     */
    public static function addPriceQualityOrder(Resource $resource, ArrayObject $input, ArrayObject $data)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }
        if( ! ResourceHelper::checkColsVisible($input, ['price_quality'])){
            return;
        }
        $work = $data->getArrayCopy();

        $position = array_map(function ($item) {
            return isset($item['price_quality']) ? ((float) $item['price_quality']) : 0;
        }, $work);


        sort($position);
        $position = array_flip(array_values(array_filter(array_map('strval', $position))));

        $total = count($work);

        array_walk($work, function (&$product) use (&$position, $total) {
            $product['price_quality_order'] = (is_null($product['price_quality']) or ! isset($position[(string) $product['price_quality']])) ? null : $total - $position[(string) $product['price_quality']] --;
        });

        $data->exchangeArray($work);
    }


    /**
     * TODO: this might be totally not needed, it should happen in click listener.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function setKomparuVerzekeringenUrl(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! ResourceHelper::checkColsVisible($input, ['company.name', 'polis.website'])){
            return;
        }

        foreach($output as &$row){
            if($row['url'] != '#'){
                continue;
            }
            $company   = array_get($row, 'company.name');
            $funnelUid = array_get($row, 'polis.website');
            $website   = array_get($input, 'website');
            if($funnelUid && $company){
                $row['url'] = sprintf('%s://%s.komparu-verzekeringen.%s/%s/%s', WebsiteHelper::protocol(), $company, WebsiteHelper::tld(true), $funnelUid, $website);
            }
        }
    }


    /**
     * Basically, for category insurances we have special user that have a seperate seed
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function addVolmachtUserCondition(Resource $resource, ArrayObject $input)
    {
        $inputArr = $input->getArrayCopy();
        if( ! isset($inputArr['product']) || ! isset($inputArr['user']) || ! in_array($inputArr['user'], self::$volmachtUsers)){
            return;
        }
        if (!is_array($inputArr['product']))
            $inputArr['product'] = [$inputArr['product']];

        foreach($inputArr['product'] as $key => $product){
            $inputArr['product'][$key] = $product . '_U' . $inputArr['user'];
        }

        $input->exchangeArray($inputArr);
    }

    /**
     * When a Lancyr product is requested and the years without damage input parameter is greater than 4, the no claim
     * value is cheaper than normal. Specifically, it is 9.08 euros. This transformation only takes place when
     * product.carinsurance is called, there is only one result and the title of the product corresponds to Lancyr.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function checkLancyrNoClaimProtection(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }

        if($resource->name != 'product.carinsurance' || count($output) > 3 || ! $input->offsetExists(ResourceInterface::YEARS_WITHOUT_DAMAGE)){
            return;
        }
        $yearsWithoutDamage = $input->offsetGet(ResourceInterface::YEARS_WITHOUT_DAMAGE);
        $payment_period     = $input->offsetExists(ResourceInterface::PAYMENT_PERIOD) ? $input->offsetGet(ResourceInterface::PAYMENT_PERIOD) : 1;
        foreach($output as $row => $data){
            if($data[ResourceInterface::__ID] == \CarinsuranceDataSeeder::CAR_LANCYR_ID){
                if($yearsWithoutDamage > 4){
                    $output[$row][ResourceInterface::NO_CLAIM_VALUE] = 9.08 * $payment_period;
                    if(isset($input[ResourceInterface::NO_CLAIM]) && $input[ResourceInterface::NO_CLAIM] == 1){
                        // We deduct the difference from the total too (hardcoded - price of regular noclaim is 12,10)
                        $output[$row][ResourceInterface::PRICE_ACTUAL] -= 3.02 * $payment_period;
                    }
                }
            }
        }
    }

    /**
     * Lancyr wants their 'Conditions' (Voorwaarden) links a bit more custom.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function tweakLancyrConditions(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }
        if($resource->name != 'product.carinsurance'){
            return;
        }
        foreach($output as $row => $data){
            if(in_array($data[ResourceInterface::__ID], \CarinsuranceDataSeeder::CAR_LANCYR_PRODUCT_IDS) && isset($output[$row]['conditions'])) {
                // Put 'coverage' as a 'wa' subitem so it gets translated as a specific 'WA' text.
                if (isset($output[$row]['conditions']['coverage'])) {
                    $output[$row]['conditions'] += [
                        'wa' => $output[$row]['conditions']['coverage'],
                    ];
                    unset($output[$row]['conditions']['coverage']);
                }
                // Remove the 'bc' link when you have 'vc' (not relevant for Lancyr)
                if (isset($output[$row]['conditions']['bc'], $output[$row]['conditions']['vc'])){
                    unset($output[$row]['conditions']['bc']);
                }
                // Rename the translated 'passenger_insurance_damage' back, so it gets properly translated in 'code'
                if (isset($output[$row]['conditions']['Schadeverzekering inzittenden'])) {
                    $output[$row]['conditions']['passenger_insurance_damage'] = $output[$row]['conditions']['Schadeverzekering inzittenden'];
                    unset($output[$row]['conditions']['Schadeverzekering inzittenden']);
                }
            }
        }
    }

    /**
     * If a premium call fails, the price of the product will be 0. These products should not be shown.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     */
    public static function filterOutMissingPremiums(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!empty($input[OptionsListener::OPTION_NO_PROPAGATION]) || !empty($input['debug'])){
            return;
        }

        $products = [];
        foreach($output->getArrayCopy() as $key => $value){
            if($value[ResourceInterface::PRICE_ACTUAL] > 0 && $value[ResourceInterface::PRICE_DEFAULT] > 0){
                $products[] = $value;
            }
        }
        $output->exchangeArray($products);
        //Recalculate the count in the header after filtering products
        if (!(strpos(php_sapi_name(), 'cli') !== false))
            header("X-Total-Count: " . count($products));
    }
}