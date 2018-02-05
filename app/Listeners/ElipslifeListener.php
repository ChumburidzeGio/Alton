<?php
/**
 * Created by PhpStorm.
 * User: kristian
 * Date: 19/10/2017
 * Time: 16:19
 */

namespace App\Listeners\Resources2;

use App\Exception\InvalidResourceInput;
use App\Exception\PrettyServiceError;
use App\Helpers\ElipslifeHelper;
use App\Helpers\ResourceFilterHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Resources\Elipslife\Methods\BmiListing;
use ArrayObject;
use Carbon\Carbon;


class ElipslifeListener
{
    //Used for calculating coverage
    const ELIPSLIFE_BASE = 100000;

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.product.elipslife.process.after', [$this, 'processProductAfter']);
        $events->listen('resource.contract.elipslife.process.after', [$this, 'processBmi']);
        $events->listen('resource.contract.elipslife.process.input', [$this, 'processContractInput']);
        $events->listen('resource.bmi_data.elipslife.process.input', [$this, 'processWeightAndHeight']);
        //$events->listen('resource.bmi_data.elipslife.process.after', [$this, 'removeDuplicateBmiListings']); //Not really needed i believe
    }


    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     * @param $action
     * @throws InvalidResourceInput
     * @throws PrettyServiceError
     */
    public function processProductAfter(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        //In case this is not INDEX or SHOW, we should never execute anything here
        if(!in_array($action, [RestListener::ACTION_SHOW, RestListener::ACTION_INDEX]))
            return;

        //inputs
        $birthdate = array_get($input, ResourceInterface::BIRTHDATE,'01-06-1985');
        $gender    = array_get($input, ResourceInterface::GENDER,'male');
        $smoker    = array_get($input, ResourceInterface::SMOKER,false);
        $coverage  = array_get($input, ResourceInterface::COVERAGE_AMOUNT, 100000);
        $productID = array_get($input, ResourceInterface::PRODUCT_ID, 1);

        /*
         * If this is not set, we have not yet reached the Term Insurance page, and
         * we want to display default period on the page before (Startpage).
         */
        $years_insured1 = array_get($input, ResourceInterface::YEARS_INSURED, 20);


        //Fix some stuffs
        $age    = Carbon::createFromTimestamp(strtotime($birthdate))->age;
        $gender = $this->genderToString($gender);
        $smoker = ResourceFilterHelper::strToBool($smoker);

        //Find premium
        try{
            $premiums = ResourceHelper::callResource2('premium.elipslife', [
                ResourceInterface::AGE        => $age,
                ResourceInterface::GENDER     => $gender,
                ResourceInterface::SMOKER     => $smoker,
                ResourceInterface::PRODUCT_ID => $productID,
            ]);
        } catch(InvalidResourceInput $e) {
            $messages = $e->getMessages();
            if (isset($messages[ResourceInterface::AGE])) {
                throw new InvalidResourceInput($resource,[ResourceInterface::BIRTHDATE => 'Nur Personen zwischen 18 und 71 Jahren können versichert werden'], $input->getArrayCopy(), 'Geburtsdag is nicht gut');
            }
        }


        //Any complaining to do?
        if(count($premiums) === 0)
            throw new PrettyServiceError($resource, [], 'Premium not found');

        //Filter premiums by price
        $premium = head(array_sort($premiums, function($v){ return floatval($v['price']); }));

        //Calc price for selected coverage
        $fixedPrice = (int)$coverage / self::ELIPSLIFE_BASE * $premium[ResourceInterface::PRICE];

        //Single
        if ($action === RestListener::ACTION_SHOW)
        {
            $output->exchangeArray(
                array_merge($output->getArrayCopy(), [
                    ResourceInterface::PRICE           => $fixedPrice,
                    ResourceInterface::YEARS_INSURED   => $years_insured1,
                    ResourceInterface::COVERAGE_AMOUNT => $coverage,
                    'input'  => $input->getArrayCopy(),
                ])
            );
        }

        //Index
        //TODO: Check if this is needed, because there is only one product and we do not need pricing on those results (Imo)
        if($action === RestListener::ACTION_INDEX)
        {
            $products = [];
            foreach ($output->getArrayCopy() as $product) {
                $products[] = array_merge($product, [
                        ResourceInterface::PRICE           => $fixedPrice,
                        ResourceInterface::YEARS_INSURED   => $years_insured1,
                        ResourceInterface::COVERAGE_AMOUNT => $coverage,
                    ]);
            }
            $output->exchangeArray($products);
        }

    }


    /**
     * Magically fix any gender shit we might have...
     */
    private function genderToString($input)
    {
        return (strtolower($input) === 'male' || $input === '1' || $input === 1) ? 'male' : 'female';
    }

    private function convertIntToBool($int)
    {
        return (intval($int) === 1) ? true : false;
    }

    /**
     * TODO: Make use of this when creating the contract.
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     * @param $action
     * @return ArrayObject
     */
    public function processBmi(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if(isset($input[ResourceInterface::WEIGHT]) && isset($input[ResourceInterface::HEIGHT]))
        {
            $height = array_get($input, ResourceInterface::HEIGHT);
            $weight = array_get($input, ResourceInterface::WEIGHT);

            $output->offsetSet(
                ResourceInterface::BMI,
                $this->calculateBMI($height, $weight)
            );

            $output->offsetSet(
                ResourceInterface::HEIGHT,
                ElipslifeHelper::processHeightToCM($height)
            );
        }
    }


    /**
     * @param $height Height in CM [without decimal(s)] and in Meters with decimal(s).
     * @param $weight Weight in Kilograms
     * @return float|int
     */
    private function calculateBMI($height, $weight)
    {
        return ($weight / $this->processHeightToCM($height) / $this->processHeightToCM($height)) * 10000;
    }


    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param $action
     */
    public function processWeightAndHeight(Resource $resource, ArrayObject $input, $action)
    {
        if(!$input->offsetExists(ResourceInterface::HEIGHT) || !$input->offsetExists(ResourceInterface::WEIGHT)){
            return;
        }

        $heights = BmiListing::possibleHeightRanges;
        $weights = BmiListing::possibleWeightRanges;

        foreach($heights as $height){
            if($height[ResourceInterface::HEIGHT_FROM] <= $input->offsetGet(ResourceInterface::HEIGHT) && $height[ResourceInterface::HEIGHT_TO] >= $input->offsetGet(ResourceInterface::HEIGHT)){
                $input->offsetSet(ResourceInterface::HEIGHT_FROM, $height[ResourceInterface::HEIGHT_FROM]);
                $input->offsetSet(ResourceInterface::HEIGHT_TO, $height[ResourceInterface::HEIGHT_TO]);
                //$input->offsetUnset(ResourceInterface::HEIGHT);
                break;
            }
        }
        foreach($weights as $weight){
            if($weight[ResourceInterface::WEIGHT_FROM] <= $input->offsetGet(ResourceInterface::WEIGHT) && $weight[ResourceInterface::WEIGHT_TO] >= $input->offsetGet(ResourceInterface::WEIGHT)){
                $input->offsetSet(ResourceInterface::WEIGHT_FROM, $weight[ResourceInterface::WEIGHT_FROM]);
                $input->offsetSet(ResourceInterface::WEIGHT_TO, $weight[ResourceInterface::WEIGHT_TO]);
                //$input->offsetUnset(ResourceInterface::WEIGHT);
                break;
            }
        }
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param $action
     */
    public function processContractInput(Resource $resource, ArrayObject $input, $action)
    {
        if(!$input->offsetExists(ResourceInterface::PRODUCT_ID)){
            $input->offsetSet(ResourceInterface::PRODUCT_ID, 1);
        }
    }


    /**
     * Removes all duplicated items from the result.
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $output
     * @param $action
     */
    public function removeDuplicateBmiListings(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        $cleanListings = [];
        //In case this is not INDEX we should never execute anything here
        if($action!== RestListener::ACTION_INDEX)
            return;

        foreach($output->getArrayCopy() as $bmiListing)
        {
            $key = $bmiListing[ResourceInterface::WEIGHT_FROM]. $bmiListing[ResourceInterface::WEIGHT_TO] . $bmiListing[ResourceInterface::HEIGHT_FROM] . $bmiListing[ResourceInterface::HEIGHT_TO] . $bmiListing[ResourceInterface::TYPE];
            if(!array_key_exists($key, $cleanListings)){
                $cleanListings[$key] = $bmiListing;
            }
        }
        ksort($cleanListings);
        $output->exchangeArray($cleanListings);
    }
}