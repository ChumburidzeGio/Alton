<?php

namespace App\Listeners\Resources2;

use App\Exception\PrettyServiceError;
use App\Helpers\DocumentHelper;
use App\Helpers\Healthcare2018Helper;
use App\Helpers\IAKHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Resource;
use App\Resources\Healthcare\Healthcare;
use App\Resources\Parking2\Methods\Options;
use ArrayObject;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Komparu\Value\ValueInterface;
use Log, DB, Event;

class HealthcareListener
{
    const IGNORE_DEFAULTS = ['geboortedatum', 'geslacht', 'ingangsdatum'];

    const CURRENTLY_INSURED_FIELDS = [
        "currently_insured",
        "aanvrager.voorletters",
        "aanvrager.tussenvoegsel",
        "aanvrager.achternaam",
        "hoofdadres.straat",
        "hoofdadres.woonplaats",
        "hoofdadres.postcode",
        "hoofdadres.huisnummer",
        "hoofdadres.huisnummertoevoeging",
        "hoofdadres.telefoonnummer",
        "hoofdadres.emailAdres",
        "verzekering.iban",
        "account_number",
        "iak_funnel_disclaimer"
    ];

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.formservice.healthcare.process.after', [$this, 'processForm']);

        $events->listen('resource.form.healthcare.process.input', [$this, 'setDefaults']);


        //what's this?
        $events->listen('resource.advice.healthcare2018.process.after', [$this, 'processAdvice']);
        $events->listen('resource.pdfs.healthcare2018.process.input', [$this, 'processPDFProductIds']);

        $events->listen('resource.collectivity.healthcare2018.process.input', [$this, 'searchForCollectivityByName']);

        $events->listen('resource.contract.healthcare2018.process.input', [$this, 'processCollectivityId']);

        //big healthcare filter
        $events->listen('resource.product.healthcare2018.process.input', [$this, 'setProductDefaults']);
        $events->listen('resource.product.healthcare2018.process.input', [$this, 'setPercentage']);
        $events->listen('resource.product.healthcare2018.process.after', [$this, 'processFreechoice']);
        $events->listen('resource.product.healthcare2018.process.after', [$this, 'addCount']);
        $events->listen('resource.product.healthcare2018.order.before', [$this, 'overloadIAKRatings']);

        $events->listen('resource.mail_selection.healthcare2018.process.after', [$this, 'mailQuote']);
        $events->listen('resource.mail_single.healthcare2018.process.after', [$this, 'mailSingle']);

        $events->listen('resource.product.healthcare2018.limit.before', [$this, 'filterCompanies']);
        $events->listen('resource.coverage_values.healthcare2018.process.input', [$this, 'setCoverageDefaults']);
        $events->listen('resource.coverage_values.healthcare2018.process.after', [$this, 'removeExceptCostTypes']);
        $events->listen('resource.coverage_values.healthcare2018.process.after', [$this, 'groupCoverages']);
        $events->listen('resource.premium.healthcare2018.process.input', [$this, 'convertBirthdate']);
        $events->listen('resource.cost_types.healthcare2018.process.input', [$this, 'processQuery']);

        $events->listen('resource.export.healthcare2018.process.after', [$this, 'processExport']);

        $events->listen('resource.company.healthcare2018.process.after', [$this, 'filterIakIfNoCollectivity']);

        //Top List (Daisycon)
        $events->listen('resource.top3listdaisycon.healthcare2018.process.after', [$this, 'top3AddProduct']);

        //Top list default
        $events->listen('resource.top3list.healthcare2018.process.after', [$this, 'top3AddProduct']);
        $events->listen('resource.top5list.healthcare2018.process.after', [$this, 'top5AddProduct']);
    }

    public function processCollectivityId(Resource $resource, ArrayObject $input, $action)
    {
        if(!$input->offsetExists(ResourceInterface::COLLECTIVITY_ID) || !$input->offsetExists(ResourceInterface::USER ) || !$input->offsetExists('applicant'))
            return;
        if($input->offsetGet(ResourceInterface::USER) != IAKHelper::USER_ID)
            return;

        //Get the product if from applicant
        $productId = null;
        foreach (['applicant', 'applicant_partner'] as $requester){
            $firstLetter = $input[$requester]['product_id'][0];
            if($firstLetter === 'H'){
                //We have found a zorgweb product do the call and get the base id
                $product = DB::connection('mysql_product')->table('product_healthcare2018')->where('__id', $input[$requester]['product_id'])->get();
                $productId = head($product)->base_id;
                break;
            }
        }

        if($productId){
            //Get the product from collectivity products to map to the correct collectivity id (provider_collectivity_id column)
            $collectivityProduct = head(DB::connection('mysql_product')->table('collectivity_products_healthcare2018')->where('product_id', $productId)->where(ResourceInterface::COLLECTIVITY_ID, $input->offsetGet(ResourceInterface::COLLECTIVITY_ID))->get());
            if($collectivityProduct){
                $input->offsetSet('collectivity_for_zorgweb', $collectivityProduct->provider_collectivity_id);
            }
        }

    }


    public function top5AddProduct(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        $providerIds = array_pluck($output->getArrayCopy(), ResourceInterface::PROVIDER_ID);


        //Get products
        $products = ResourceHelper::callResource2('product.healthcare2018', [
            OptionsListener::OPTION_LIMIT  => 5,
            ResourceInterface::PROVIDER_ID => $providerIds,
            ResourceInterface::BIRTHDATE   => '1980-01-01',
            ResourceInterface::OWN_RISK    => 385,
            ResourceInterface::CO_INSURED  => 0,
            ResourceInterface::GROUP_BY  => 'provider',
        ] + array_only($input->getArrayCopy(), [ResourceInterface::USER, ResourceInterface::WEBSITE]));

        $productKeys  = [];

        // provider_id to arrayKeys:
        foreach($products as $product)
            $productKeys[$product[ResourceInterface::PROVIDER_ID]] = $product;

        //append company to each "rank"
        foreach($output as &$row){
            if(!array_key_exists($row[ResourceInterface::PROVIDER_ID], $productKeys)) {
                $row[ResourceInterface::PRODUCT] = [];
                continue;
            }

            $productRow                      = $productKeys[$row[ResourceInterface::PROVIDER_ID]];
            $row[ResourceInterface::PRODUCT] = $productRow;
            $row[ResourceInterface::PRICE_ACTUAL] = $productRow[ResourceInterface::PRICE_ACTUAL];

            //$row[..] = ... etc
        }
    }


    public function top3AddProduct(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        $providerIds = array_pluck($output->getArrayCopy(), ResourceInterface::PROVIDER_ID);

        //Get products
        $products = ResourceHelper::callResource2('product.healthcare2018', [
            OptionsListener::OPTION_LIMIT  => 3,
            ResourceInterface::PROVIDER_ID => $providerIds,
            ResourceInterface::BIRTHDATE   => '1980-01-01',
            ResourceInterface::OWN_RISK    => 385,
            ResourceInterface::CO_INSURED  => 0,
            ResourceInterface::GROUP_BY  => 'provider',
        ] + array_only($input->getArrayCopy(), [ResourceInterface::USER, ResourceInterface::WEBSITE]));
        $productKeys  = [];

        // provider_id to arrayKeys:
        foreach($products as $product)
            $productKeys[$product[ResourceInterface::PROVIDER_ID]] = $product;

        //append company to each "rank"
        foreach($output as &$row){
            if(!array_key_exists($row[ResourceInterface::PROVIDER_ID], $productKeys)) {
                $row[ResourceInterface::PRODUCT] = [];
                continue;
            }

            $productRow                           = $productKeys[$row[ResourceInterface::PROVIDER_ID]];
            $row[ResourceInterface::PRODUCT]      = $productRow;
            $row[ResourceInterface::PRICE_ACTUAL] = $productRow[ResourceInterface::PRICE_ACTUAL];

            //$row[..] = ... etc
        }
    }

    public function processQuery(Resource $resource, ArrayObject $input, $action)
    {
        if($input->offsetExists('q')){
            $input->offsetSet('name', '*' . $input->offsetGet('q') . '*');
        }
    }

    //can't get default order to work
    public function setCoverageDefaults(Resource $resource, ArrayObject $input, $action)
    {
        if($input->offsetExists(OptionsListener::OPTION_ORDER)){
            return;
        }
        $input->offsetSet(OptionsListener::OPTION_ORDER, ResourceInterface::ORDER);
    }

    public static function removeExceptCostTypes(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(!isset($input['except'])){
            return;
        }

        $costTypesToRemove = explode(',', $input['except']);

        $processed = array_filter($output->getArrayCopy(), function ($item) use ($costTypesToRemove){
            return !in_array($item['alias'], $costTypesToRemove);
        });

        $output->exchangeArray($processed);

    }

    public static function groupCoverages(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $costTypeIds = [];
        foreach($output as $coverage){
            if(isset($coverage[ResourceInterface::COST_TYPE_ID])) {
                $costTypeIds[] = $coverage[ResourceInterface::COST_TYPE_ID];
            }
        }
        $costTypeIds = array_unique($costTypeIds);

        $costTypes       = ResourceHelper::callResource2('cost_types.healthcare2018', [
            ResourceInterface::LIMIT => ValueInterface::INFINITE,
            ResourceInterface::__ID => $costTypeIds
        ] + array_only($input->getArrayCopy(), [ResourceInterface::USER, ResourceInterface::WEBSITE]));
        $costTypeIndexed = [];
        foreach($costTypes as $costType){
            $costTypeIndexed[$costType[ResourceInterface::__ID]] = $costType;
        }

        $coverageValues = $output->getArrayCopy();
        $key            = ResourceInterface::ALIAS;

        $groupedValues = array_reduce($coverageValues, function ($groupedValues, $value) use ($key, $costTypeIndexed) {
            if( ! isset($groupedValues[$value[$key]])){
                $groupedValues[$value[$key]] = ['name' => $value['alias'], 'label' => $value['alias'], 'options' => []];
            }
            $groupedValues[$value[$key]]['options'][] = array_only($value, [
                    ResourceInterface::__ID,
                    ResourceInterface::LABEL,
                    ResourceInterface::ENABLED,
                    ResourceInterface::VALUE,
                    ResourceInterface::PERCENTAGE,
                    ResourceInterface::DESCRIPTION,
                    ResourceInterface::ORDER,
                    ResourceInterface::ACTIVE,
                ]) + [ResourceInterface::NAME => $value[ResourceInterface::VALUE] . '_' . $value[ResourceInterface::PERCENTAGE]];
            if(isset($costTypeIndexed[$value[ResourceInterface::COST_TYPE_ID]])){
                $groupedValues[$value[$key]][ResourceInterface::TITLE]       = $costTypeIndexed[$value[ResourceInterface::COST_TYPE_ID]][ResourceInterface::NAME];
                $groupedValues[$value[$key]][ResourceInterface::DESCRIPTION] = $costTypeIndexed[$value[ResourceInterface::COST_TYPE_ID]][ResourceInterface::DESCRIPTION];
            }

            if(in_array($value[ResourceInterface::COST_TYPE_ID], [1337, 666])){
                $groupedValues[$value[$key]]['virtual'] = true;
            }

            return $groupedValues;
        }, []);


        // set percentage to null for every group that has only0 or 100 percentages
        $groupedValues = array_map(function ($values) {

            // find all percentages present in a group
            $percentages = array_unique(array_map(function ($value) {
                return array_get($value, ResourceInterface::PERCENTAGE);
            }, $values['options']));

            // if there's only one percentage and it's 0 or 100
            if(count($percentages) == 1 && in_array($percentages[0], [0, 100])){

                // set them all to null
                $values['options'] = array_map(function ($value) {
                    $value[ResourceInterface::PERCENTAGE] = null;
                    $value[ResourceInterface::NAME]       = $value[ResourceInterface::VALUE];

                    return $value;
                }, $values['options']);
            }

            // Make double sure we're sorting on 'order'
            $values['options'] = array_values(array_sort($values['options'], function ($x) {
                return $x['order'];
            }));

            return $values;

        }, $groupedValues);

        usort($groupedValues, function ($a, $b) {
            return strcmp(array_get($a, 'title'), array_get($b, 'title'));
        });

        $output->exchangeArray(array_values($groupedValues));
    }

    public static function addCount(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action != RestListener::ACTION_INDEX && $action != RestListener::ACTION_SHOW){
            return;
        }
        $headers = headers_list();
        foreach($headers as $rawHeader){
            list($header, $value) = explode(':', $rawHeader);
            if($header === 'Total-Count-Internal'){
                $value = intval(trim($value));
                header('X-Total-Count: ' . $value);
                header_remove('Total-Count-Internal');
                break;
            }
        }
    }

    public static function processFreechoice(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action != RestListener::ACTION_INDEX && $action != RestListener::ACTION_SHOW){
            return;
        }

        //get the free choice labels
        $freechoiceVars = [
            ResourceInterface::COST_TYPE_ID => 666,
        ];
        if($input->offsetExists(ResourceInterface::WEBSITE)){
            $freechoiceVars[ResourceInterface::WEBSITE] = $input->offsetGet(ResourceInterface::WEBSITE);
        }
        if($input->offsetExists(ResourceInterface::USER)){
            $freechoiceVars[ResourceInterface::USER] = $input->offsetGet(ResourceInterface::USER);
        }

        $freechoiceResource = ResourceHelper::callResource2('coverage_values.healthcare2018', $freechoiceVars);
        $freechoiceLabels   = [];
        if(isset($freechoiceResource[0]['options'])){
            foreach($freechoiceResource[0]['options'] as $res){
                $freechoiceLabels[$res['value']] = array_only($res, [ResourceInterface::LABEL, ResourceInterface::DESCRIPTION]);
            }
        }


        $products = $action == RestListener::ACTION_SHOW ? [$output->getArrayCopy()] : $output->getArrayCopy();

        $retArr = [];
        foreach($products as $row){
            if( ! isset($row['free_choice'], $freechoiceLabels[$row['free_choice']])){
                $retArr[] = $row;
                continue;
            }
            $row['free_choice_label']       = $freechoiceLabels[$row['free_choice']][ResourceInterface::LABEL];
            $description                    = array_get($freechoiceLabels[$row['free_choice']], ResourceInterface::DESCRIPTION);
            $contractUrl                    = array_get($row, 'company.' . ResourceInterface::URL_CONTRACTED_CARE);
            $description                    = str_replace('%company.' . ResourceInterface::URL_CONTRACTED_CARE . '%', $contractUrl, $description);
            $row['free_choice_description'] = $description;
            $retArr[]                       = $row;
        }

        if($action == RestListener::ACTION_SHOW){
            $retArr = head($retArr);
        }

        $output->exchangeArray($retArr);
    }

    public static function overloadIAKRatings(Resource $resource, ArrayObject $input, ArrayObject $output, $stuff, $action)
    {
        if(array_get($input, ResourceInterface::USER) != IAKHelper::USER_ID){
            return;
        }

        if($action != RestListener::ACTION_INDEX && $action != RestListener::ACTION_SHOW){
            return;
        }


        $overloadedIAKRatings = [
            202901 => 9, // IAK 4,5 stars
            202669 => 9, // CZ 4,5 stars
            202686 => 8, // ONVZ 4 stars
            202736 => 8, // Menzis 4 stars
            202704 => 8, // Zilveren Kruis 4 stars
            202660 => 7, // VGZ 3,5 stars
        ];

        foreach($output as $nr => $item){
            if(isset($overloadedIAKRatings[$item[ResourceInterface::PROVIDER_ID]])){
                $item[ResourceInterface::RATING] = $overloadedIAKRatings[$item[ResourceInterface::PROVIDER_ID]];
                $output[$nr]                     = $item;
            }
        }
    }

    /*
     * Acttive and enabled products
     */

    public function setProductDefaults(Resource $resource, ArrayObject $input, $action)
    {
        //check if this user has right
        if($input->offsetExists(ResourceInterface::USER)){
            $rights = DB::connection('mysql')->select("SELECT * FROM rights WHERE user_id = ? AND product_type_id = 48", [$input->offsetGet(ResourceInterface::USER)]);
            foreach($rights as $right) {
                if($right->key =='group_by' && (array_get($input, ResourceInterface::GROUP_BY) != 'single_product')) {
                    $input->offsetSet(ResourceInterface::GROUP_BY, $rights[0]->value);
                    continue;
                }

                if($right->key =='daisycon_widget') {
                    $input->offsetSet(ResourceInterface::DAISYCON, true);
                    continue;
                }

                if($right->key =='daisycon_forward') {
                    $input->offsetSet(ResourceInterface::DAISYCON_FORWARD, true);
                    continue;
                }

            }
        }

        if( ! $input->offsetExists(ResourceInterface::ENABLED)){
            $input->offsetSet(ResourceInterface::ENABLED, true);
        }
    }

    /*
     * Only show products with a company
     */
    public static function filterCompanies(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if($input->offsetExists('_no_propagation')){
            return;
        }
        if($input->offsetExists(ResourceInterface::ACTIVE)){
            $output->exchangeArray(array_filter($output->getArrayCopy(), function ($row) {
                return array_has($row, 'company.__id');
            }));
        }
    }

    public function searchForCollectivityByName(Resource $resource, ArrayObject $input, $action)
    {
        if( ! isset($input['search'])){
            return;
        }

        $input->offsetSet(ResourceInterface::LABEL, '*' . $input['search'] . '*');
        $input->offsetUnset('search');
    }

    public function distinctBaseInsurance(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $baseInsurances = [];
        $output->exchangeArray(array_values(array_filter($output->getArrayCopy(), function ($row) use (&$baseInsurances) {
            if(isset($baseInsurances[$row[ResourceInterface::BASE_ID]])){
                return false;
            }else{
                $baseInsurances[$row[ResourceInterface::BASE_ID]] = true;

                return true;
            }
        })));
    }

    public function filterEmptyResults(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if( ! empty($input[OptionsListener::OPTION_NO_PROPAGATION])){
            return;
        }

        $output->exchangeArray(array_values(array_filter($output->getArrayCopy(), function ($row) {
            return ! is_null($row[ResourceInterface::PRICE_BASE]);
        })));
    }

    public function toAge(Resource $resource, ArrayObject $input, $action)
    {

        if( ! $input->offsetExists(ResourceInterface::AGE)){
            $age = Healthcare::getAgeFromBirthdate($input[ResourceInterface::BIRTHDATE]);
            cw('set age to ' . $age);
        }else{
            $age = $input->offsetGet(ResourceInterface::AGE);
        }

        $input->offsetUnset(ResourceInterface::BIRTHDATE);

        $input->offsetSet(ResourceInterface::AGE_FROM, '<=' . $age);
        $input->offsetSet(ResourceInterface::AGE_TO, '>' . $age);
    }

    /**
     * form service is a service used to wrap the healthcare form
     */
    public function mailSingle(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action != 'store'){
            return;
        }
        $session                           = json_decode(urldecode($input->offsetGet(ResourceInterface::SESSION)), true);
        $mailSelection                     = array_get(array_get($session, 'keep-data'), 'mail_single.healthcare2018');
        $products['products']['applicant'] = Healthcare2018Helper::getProductStructure($mailSelection[ResourceInterface::PRODUCT_ID], $input->offsetGet(ResourceInterface::OWN_RISK), $input->offsetGet(ResourceInterface::BIRTHDATE),
            $input->offsetGet(ResourceInterface::USER), $input->offsetGet(ResourceInterface::WEBSITE), array_get($input->getArrayCopy(), ResourceInterface::COLLECTIVITY_ID, null), ['hide_free_toppings' => true]);


        $orderData = [
            ResourceInterface::USER       => $input->offsetGet(ResourceInterface::USER),
            ResourceInterface::WEBSITE    => $input->offsetGet(ResourceInterface::WEBSITE),
            ResourceInterface::IP         => $input->offsetGet(ResourceInterface::IP),
            ResourceInterface::SESSION_ID => $input->offsetGet(ResourceInterface::SESSION_ID),
            ResourceInterface::PRODUCT_ID => $mailSelection[ResourceInterface::PRODUCT_ID],
            ResourceInterface::PRODUCT    => json_encode($products),
            ResourceInterface::SESSION    => json_encode($session),
            ResourceInterface::OUID       => uniqid(),
            ResourceInterface::STATUS     => ['QUOTE']
        ];

        $order = ResourceHelper::callResource2('order.healthcare2018', $orderData, RestListener::ACTION_STORE);
        Event::fire('email.notify', [
            'healthcare2018',
            'request.quote',
            $order['__id'],
            $input->offsetGet(ResourceInterface::WEBSITE),
            $input->offsetGet(ResourceInterface::USER),
            $input->offsetGet(ResourceInterface::TO_EMAIL),
        ]);
    }


    /**
     * form service is a service used to wrap the healthcare form
     */
    public function mailQuote(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action != 'store'){
            return;
        }
        $session              = json_decode(urldecode($input->offsetGet(ResourceInterface::SESSION)), true);
        $flattenedSession     = Healthcare2018Helper::sessionPrakker($session);
        $collectivyId         = isset($flattenedSession[ResourceInterface::COLLECTIVITY_ID]) ? $flattenedSession[ResourceInterface::COLLECTIVITY_ID] : 0;
        $productId            = null;
        $products['products'] = [];
        foreach(Healthcare2018Helper::PERSONS as $person){
            if( ! isset($flattenedSession[$person . '.' . ResourceInterface::BIRTHDATE], $flattenedSession[$person . '.' . ResourceInterface::OWN_RISK], $flattenedSession[$person . '.' . ResourceInterface::PRODUCT_ID])){
                continue;
            }
            $products['products'][$person] = Healthcare2018Helper::getProductStructure($flattenedSession[$person . '.' . ResourceInterface::PRODUCT_ID], $flattenedSession[$person . '.' . ResourceInterface::OWN_RISK],
                $flattenedSession[$person . '.' . ResourceInterface::BIRTHDATE], $input->offsetGet(ResourceInterface::USER), $input->offsetGet(ResourceInterface::WEBSITE), $collectivyId, ['hide_free_toppings' => true]);
        }


        $orderData = [
            ResourceInterface::USER       => $input->offsetGet(ResourceInterface::USER),
            ResourceInterface::WEBSITE    => $input->offsetGet(ResourceInterface::WEBSITE),
            ResourceInterface::IP         => $input->offsetGet(ResourceInterface::IP),
            ResourceInterface::SESSION_ID => $input->offsetGet(ResourceInterface::SESSION_ID),
            ResourceInterface::PRODUCT_ID => $productId,
            ResourceInterface::PRODUCT    => json_encode($products),
            ResourceInterface::SESSION    => json_encode($session),
            ResourceInterface::OUID       => uniqid(),
            ResourceInterface::STATUS     => ['QUOTE']
        ];

        $order = ResourceHelper::callResource2('order.healthcare2018', $orderData, RestListener::ACTION_STORE);

        Event::fire('email.notify', [
            'healthcare2018',
            'request.quote',
            $order['__id'],
            $input->offsetGet(ResourceInterface::WEBSITE),
            $input->offsetGet(ResourceInterface::USER),
            $input->offsetGet(ResourceInterface::TO_EMAIL)
        ]);
    }


    /**
     * form service is a service used to wrap the healthcare form
     */
    public function processForm(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        if($action != 'store'){
            return;
        }
        $formResource = Resource::where('name', 'form.healthcare')->firstOrFail();
        $formInputs   = [];
        foreach($formResource->fields as $field){
            $formInputs[] = $field->name;
        }
        $formResult = ResourceHelper::call($formResource, 'index', array_only($input->getArrayCopy(), $formInputs));
        $output->exchangeArray(self::invokeWebservice($input, new ArrayObject($formResult)));
    }

    public function processAdvice(Resource $resource, ArrayObject $input, ArrayObject $output, $action)
    {
        $rawOutput = $output->getArrayCopy();

        $rawOutput = array_map(function ($row) use ($input, $rawOutput) {
            return self::applyConditions($row, $input, $rawOutput);
        }, $rawOutput);

        //take out conditional fields
        $rawOutput = array_filter($rawOutput, function ($row) use ($input) {
            return ! isset($row[ResourceInterface::ENABLED]) || $row[ResourceInterface::ENABLED];
        });

        $rawOutput = array_filter($rawOutput, function (&$row) {
            unset($row[ResourceInterface::ENABLED]);

            return $row;
        });

        $output->exchangeArray(array_values($rawOutput));
    }

    public function setPercentage(Resource $resource, ArrayObject $input, $action)
    {
        // we need to clone the input
        // because otherwise we would get into an infinite loop
        foreach(clone $input as $key => $value){
            if( ! preg_match('/^[\d]/', $key)){
                continue;
            }
            $searchTerms = explode('_', $value);

            $input->offsetSet($key, $searchTerms[0]);
            if(isset($searchTerms[1]) && $searchTerms[1]){
                $input->offsetSet($key . '_p', $searchTerms[1]);
            }
        }
    }

    public function convertBirthdate(Resource $resource, ArrayObject $input, $action)
    {
        if($action == 'index'){
            if( ! empty($input[ResourceInterface::BIRTHDATE])){
                $age = Healthcare::getAgeFromBirthdate($input[ResourceInterface::BIRTHDATE]);

                $input[ResourceInterface::AGE_FROM] = '<' . ($age + 1);
                $input[ResourceInterface::AGE_TO]   = '>' . ($age);

                if( ! isset($input[ResourceInterface::CO_INSURED]) && $age >= 18){
                    $input[ResourceInterface::CO_INSURED] = 0;
                }
            }
        }
    }

    public function processPDFProductIds(Resource $resource, ArrayObject $input, $action)
    {
        if($input->offsetExists(ResourceInterface::PRODUCT_ID)){
            $product = head(ResourceHelper::callResource2('product.healthcare2018', [ResourceInterface::__ID => $input[ResourceInterface::PRODUCT_ID]]));
            if(isset($product['child_source_ids'])){
                //We have a combo product, take the base id together with the child id and use them as the product ids
                $processedBaseId = $this->getProductId($product['company']['resource_id'], $product['base_id'], 'base');

                $unprocessedChildIds = explode(',', $product['child_source_ids']);
                $processedChildIds   = [];

                foreach($unprocessedChildIds as $unprocessedChildId){
                    $processedChildIds[] = $this->getProductId($product['company']['resource_id'], $unprocessedChildId, 'additional');
                }
                //Add the base_id to the beginning
                array_unshift($processedChildIds, $processedBaseId);

                $input->offsetSet(ResourceInterface::PRODUCT_ID, $processedChildIds);
            }
        }
    }

    private function getProductId($company_id, $product_id, $coverage_type)
    {
        $prefix     = ($company_id != IAKHelper::COMPANY_ID) ? ($coverage_type === 'base' ? 'H' : 'A') : '';
        $product_id = $prefix . $product_id;

        return $product_id;
    }

    public function setDefaults(Resource $resource, ArrayObject $input, $action)
    {
        if($action != 'index'){
            return;
        }
        $input->offsetSet('_' . ResourceInterface::ORDER, ResourceInterface::ORDER);
        if($input->offsetExists(ResourceInterface::TAG)){
            $input->offsetSet('_' . ResourceInterface::TAG, $input->offsetGet(ResourceInterface::TAG));
            $input->offsetUnset(ResourceInterface::TAG);
        }
        if($input->offsetExists(ResourceInterface::RESOURCE__ID)){
            $input->offsetUnset(ResourceInterface::RESOURCE__ID);
        }
        if($input->offsetExists(ResourceInterface::__ID)){
            $input->offsetUnset(ResourceInterface::__ID);
        }
    }


    private static function invokeWebservice(ArrayObject $input, ArrayObject $output)
    {

        //special hack for current insured
        if($input->offsetExists('currently_insured')){
            if($input->offsetGet('currently_insured')){
                $outputArray = (array_values(array_filter($output->getArrayCopy(), function ($row) use ($input) {
                    return isset($row[ResourceInterface::NAME]) && in_array($row[ResourceInterface::NAME], self::CURRENTLY_INSURED_FIELDS);
                })));
                $outputArray = array_map(function ($row) use ($input) {
                    if($input->offsetExists($row[ResourceInterface::RESOURCE__ID])){
                        $row[ResourceInterface::DEFAULT_VALUE] = $input->offsetGet($row[ResourceInterface::RESOURCE__ID]);
                    }

                    return self::processDescription($row, $input);
                }, $outputArray);

                return $outputArray;
            }else{
                $input->offsetUnset('currently_insured');
            }
        }


        $mappedOutput = [];
        foreach($output->getArrayCopy() as $row){
            if($row[ResourceInterface::TYPE] == 'choice' && $row[ResourceInterface::OPTIONS]){
                $row[ResourceInterface::OPTIONS] = json_decode($row[ResourceInterface::OPTIONS], true);
            }
            $mappedOutput[$row[ResourceInterface::RESOURCE__ID]] = $row;
        }
        cw($mappedOutput);

        $resourceResults = ResourceHelper::callResource2('getform.healthcare', $input->getArrayCopy());
        $previous        = null;
        foreach($resourceResults as &$resultRow){
            if(isset($mappedOutput[$resultRow[ResourceInterface::RESOURCE__ID]])){
                $mappedOutput[$resultRow[ResourceInterface::RESOURCE__ID]][ResourceInterface::ENABLED] = true;


                //hacks
                //copy birthdate defaults
                if(str_contains($resultRow[ResourceInterface::RESOURCE__ID], 'geboortedatum')){
                    $mappedOutput[$resultRow[ResourceInterface::RESOURCE__ID]][ResourceInterface::DEFAULT_VALUE] = date("d-m-Y", strtotime($resultRow[ResourceInterface::DEFAULT_VALUE]));
                    continue;
                }

                if(str_contains($resultRow[ResourceInterface::RESOURCE__ID], 'verzekering.ingangsdatum')){
                    $datetime                                                                                    = new DateTime('tomorrow');
                    $mappedOutput[$resultRow[ResourceInterface::RESOURCE__ID]][ResourceInterface::DEFAULT_VALUE] = $datetime->format("d-m-Y");
                    continue;
                }

                if($input->offsetExists($resultRow[ResourceInterface::RESOURCE__ID])){
                    $mappedOutput[$resultRow[ResourceInterface::RESOURCE__ID]][ResourceInterface::DEFAULT_VALUE] = $input->offsetGet($resultRow[ResourceInterface::RESOURCE__ID]);
                }
                continue;
            }

            /**
             * New entry found!
             */
            cw('new entry!!');
            cw($mappedOutput);

            $resultRow[ResourceInterface::ACTIVE]  = true;
            $resultRow[ResourceInterface::ENABLED] = true;

            //unset somedefaults
            if(isset($resultRow[ResourceInterface::NAME]) && str_contains($resultRow[ResourceInterface::NAME], self::IGNORE_DEFAULTS)){
                unset($resultRow[ResourceInterface::DEFAULT_VALUE]);
            }

            //handle order of details
            if(ends_with($resultRow[ResourceInterface::RESOURCE__ID], '-details')){
                $needle = substr($resultRow[ResourceInterface::RESOURCE__ID], 0, strlen($resultRow[ResourceInterface::RESOURCE__ID]) - strlen('-details'));
                if(isset($mappedOutput[$needle])){
                    $resultRow[ResourceInterface::ORDER] = $mappedOutput[$needle][ResourceInterface::ORDER] + 50000;
                }
            }

            //issue
            $find   = array_only($resultRow, [ResourceInterface::NAME]);
            $result = ResourceHelper::callResource2('form.healthcare', $find);
            if(count($result)){
                if(isset($resultRow[ResourceInterface::NAME])){
                    Log::warning("Trying to store a new form.healthcare entry, while name is already there: " . $resultRow[ResourceInterface::NAME]);
                }
                continue;
            }

            ResourceHelper::callResource2('form.healthcare', $resultRow, RestListener::ACTION_STORE);

            $mappedOutput[$resultRow[ResourceInterface::RESOURCE__ID]] = $resultRow;

            //sort by order
            uasort($mappedOutput, function ($a, $b) {
                return ($a[ResourceInterface::ORDER] < $b[ResourceInterface::ORDER]) ? - 1 : 1;
            });
        }

        //only active, enabled, forced rowed rows
        $mappedOutput = array_filter($mappedOutput, function ($row) use ($input) {
            return self::checkVisible($row, $input);
        });


        $mappedOutput = array_map(function ($row) use ($input, $mappedOutput) {
            return self::applyConditions($row, $input, $mappedOutput);
        }, $mappedOutput);

        $mappedOutput = array_map(function ($row) use ($input) {
            return self::processDescription($row, $input);
        }, $mappedOutput);

        //take out conditional fields
        $mappedOutput = array_filter($mappedOutput, function ($row) use ($input) {
            return ! isset($row[ResourceInterface::ENABLED]) || $row[ResourceInterface::ENABLED];
        });

        $mappedOutput = array_filter($mappedOutput, function (&$row) {
            unset($row[ResourceInterface::ENABLED]);

            return $row;
        });

        //sort by order
        $sorted = Collection::make(array_values($mappedOutput))->sortBy('order')->values()->toArray();

        return $sorted;
    }


    /**
     * Check if this row should be visibleed, based on fields
     *
     * @param $row
     * @param $input
     *
     * @return bool
     */
    private static function checkVisible($row, $input)
    {
        $visible = isset($row[ResourceInterface::ENABLED]) && $row[ResourceInterface::ENABLED];

        if(isset($row[ResourceInterface::ACTIVE])){
            $visible = ($visible && $row[ResourceInterface::ACTIVE]);
        }

        if(isset($row[ResourceInterface::FORCE])){
            $visible = ($visible || $row[ResourceInterface::FORCE]);
        }

        if(isset($input['_' . ResourceInterface::TAG])){
            $visible = ($visible && isset($row[ResourceInterface::TAG]) && ($row[ResourceInterface::TAG] == $input['_' . ResourceInterface::TAG]));
        }

        return $visible;
    }


    /**
     * Apply the conditions
     * Operators: is_visible, is_not_visible, ==, !=
     * Logic: or / and
     * Actions
     * enabled, default_value
     */
    private static function applyConditions($row, $input, $outputMap)
    {


        if( ! isset($row[ResourceInterface::CONDITIONS])){
            return $row;
        }
        $conditionField = is_string($row[ResourceInterface::CONDITIONS]) ? json_decode($row[ResourceInterface::CONDITIONS], true) : $row[ResourceInterface::CONDITIONS];
        //set logic
        $logic      = isset($conditionField['logic']) ? $conditionField['logic'] : "and";
        $conditions = $conditionField['conditions'];


        $conditionsMet = [];
        foreach($conditions as $condition){
            //check if this condition is met
            if($condition['operator'] == 'is_visible'){
                $conditionsMet[] = isset($outputMap[$condition['source']]);
                continue;
            }
            if($condition['operator'] == 'is_not_visible'){
                $conditionsMet[] = ! isset($outputMap[$condition['source']]);
                continue;
            }

            if($condition['operator'] == '=='){
                $conditionsMet[] = isset($input[$condition['source']]) && ($input[$condition['source']] == $condition['value']);
                continue;
            }

            if($condition['operator'] == '!='){
                $conditionsMet[] = ! (isset($input[$condition['source']]) && ($input[$condition['source']] == $condition['value']));
                continue;
            }
        }

        $resultBoolean = array_shift($conditionsMet);
        foreach($conditionsMet as $conditionMet){
            if($logic == 'and'){
                $resultBoolean = ($resultBoolean && $conditionMet);
                continue;
            }
            $resultBoolean = ($resultBoolean || $conditionMet);
            continue;
        }

        //apply actions
        $actions = isset($conditionField['actions']) ? $conditionField['actions'] : [];
        foreach($actions as $actionKey => $actionValue){
            if($actionKey == ResourceInterface::ENABLED){
                $row[ResourceInterface::ENABLED] = $resultBoolean ? $actionValue : ! $actionValue;
            }

            if(($actionKey == ResourceInterface::DEFAULT_VALUE) && $resultBoolean){
                $row[ResourceInterface::DEFAULT_VALUE] = $actionValue;
            }
        }

        return $row;
    }


    private static function processDescription(Array $row, ArrayObject $input)
    {
        if( ! isset($row[ResourceInterface::LABEL])){
            return $row;
        }
        if(preg_match('/{{(.+)}}/', $row[ResourceInterface::LABEL], $matches)){
            $key = $matches[1];
            if($input->offsetExists($key)){
                $row[ResourceInterface::LABEL] = str_replace($matches[0], $input->offsetGet($key), $row[ResourceInterface::LABEL]);
            }
        }

        return $row;
    }


    /**
     * Export
     */

    public static function processExport(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        $user = \app('application')->user;
        if( ! $user){
            throw new PrettyServiceError($resource, $input->getArrayCopy(), "No user defined for this export");
        }

        $startDate  = strtotime(" -1 weeks");
        $orders = ResourceHelper::callResource2('order.healthcare2018',
            [OptionsListener::OPTION_LIMIT => ValueInterface::INFINITE,
             'user' => $user->id,
             OptionsListener::OPTION_TIMESTAMP => true]);

;
        //only successfull orders
        $status = $input->offsetExists('status')?$input->offsetGet('status'):'SUCCESS';
        $orders    = array_filter($orders, function ($row) use ($status, $startDate) {
            //return (str_contains($row['status'], strtoupper($status)) && $row['__id'] == 6343);
            return str_contains($row['status'], strtoupper($status)) && (strtotime($row['created_at']) > $startDate);
        });

        if ($status == 'ERROR' && $input->offsetExists('session_filter')) {
            $orders    = array_filter($orders, function ($row) use ($status, $startDate, $input) {
                //return (str_contains($row['status'], strtoupper($status)) && $row['__id'] == 6343);
                return str_contains($row['session'], $input->offsetGet('session_filter'));
            });
            //$orders = array_unique($orders);
        }

        $collected = [];
        cws('process_orders');
        foreach($orders as $row){
            $process = [];
            $session = Healthcare2018Helper::sessionPrakker(json_decode($row['session'], true));
            $product = json_decode($row['product'], true);
            if( ! isset($product['products'])){
                continue;
            }
            $data = [];
            $data['order_id'] = $row['__id'];

            // create a $dt object with the UTC timezone
            $dt = new DateTime( $row['created_at'], new DateTimeZone('UTC'));
            // change the timezone of the object without changing it's time
            $dt->setTimezone(new DateTimeZone('Europe/Amsterdam'));
            $data['time'] = $dt->format('Y-m-d H:i:s T');;

            $data['zorgweb_order_id'] = $row['zorgweb_order_id'];
            $data['status'] = $row['status'];

            //copy email to hoofd adress
            if($input->offsetExists('status') && (strtolower($input->offsetGet('status')) == 'quote')) {
                $data['hoofdadres.emailAdres'] = isset($session['to_email'])?$session['to_email']:"";
                $data['hoofdadres.telefoonnummer'] = isset($session['phone'])?$session['phone']:"";
                $data['aanvrager.achternaam'] = isset($session['to_name'])?$session['to_name']:"";
                $data['url'] = "https://iak.nl/zorgverzekering/offerte?r=cart&ouid=".$row['ouid']."&hash_type=hc2018q";
            }

            foreach(Healthcare2018Helper::MEMBER_MAPPING as $personKey => $zorgwebKey){
                if( ! isset($product['products'][$personKey])){
                    continue;
                }
                $data[$zorgwebKey . '.company_name']      = array_get($product, 'products.' . $personKey . '.total_product.company.name', '');
                $data[$zorgwebKey . '.title']             = array_get($product, 'products.' . $personKey . '.total_product.title', '');
                $data[$zorgwebKey . '.own_risk']          = array_get($product, 'products.' . $personKey . '.total_product.own_risk', '');
                $data[$zorgwebKey . '.price_actual']      = array_get($product, 'products.' . $personKey . '.total_product.price_actual', '');
                $data[$zorgwebKey . '.payment_period']    = array_get($product, 'products.' . $personKey . '.total_product.payment_period', '');
                $data[$zorgwebKey . '.product_id']        = array_get($product, 'products.' . $personKey . '.total_product.product_id', '');
                $data[$zorgwebKey . '.currently_insured'] = array_get($session, $personKey . '.currently_insured', '');
            }
            $data = array_merge($data, $session);
            $dataUnDot = [];
            foreach($data as $key => $val){
                array_set($dataUnDot,$key, $val);
            }

            //$data = array_dot()
            foreach(self::getStaticHeaders() as $head){
                $value = array_get($dataUnDot, $head, '');
                if(is_array($value)){
                    $res = '';
                    foreach($value as $arrKey => $arrValue){
                        $res .= $arrKey . ': ' . $arrValue . PHP_EOL;
                    }
                    $value = $res;
                }
                $process[$head] = $value;
            }
            $collected[] = $process;
        }
        cwe('process_orders');
        $output->exchangeArray($collected);
    }


    public function filterIakIfNoCollectivity(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        if(( ! isset($input[ResourceInterface::COLLECTIVITY_ID]) || $input[ResourceInterface::COLLECTIVITY_ID] <= 0) && isset($input['ikweet']) && $input['ikweet']){
            $output->exchangeArray(array_values(array_filter($output->getArrayCopy(), function ($company) {
                return $company[ResourceInterface::RESOURCE__ID] != IAKHelper::COMPANY_ID;
            })));
        }
    }


    private static function getStaticHeaders()
    {
        $header       = [
            'time',
            'order_id',
            'zorgweb_order_id',
            'status',
            'collectivity_id',
            'url',
            "hoofdadres.postcode",
            "hoofdadres.huisnummer",
            "hoofdadres.huisnummertoevoeging",
            "hoofdadres.straat",
            "hoofdadres.woonplaats",
            "hoofdadres.telefoonnummer",
            "hoofdadres.emailAdres",
            "verzekeringsgegevens.ingangsdatumAndersReden",
            "verzekeringsgegevens.iban",
            "verzekeringsgegevens.incassoWijze",
            "verzekeringsgegevens.betalingstermijnString",
        ];
        $persons      = ['aanvrager', 'partner', 'kinderen.0', 'kinderen.1', 'kinderen.2', 'kinderen.3', 'kinderen.4', 'kinderen.5', 'kinderen.6', 'kinderen.7'];
        $personFields = [
            "geboortedatum",
            "geslacht",
            "voorletters",
            "tussenvoegsel",
            "achternaam",
            "burgerservicenummerString",
            "nationaliteit",
            "zorgvragenMap",
            "company_name",
            "title",
            "own_risk",
            "price_actual",
            "payment_period",
            "product_id",
            "currently_insured",
        ];
        foreach($persons as $person){
            foreach($personFields as $fields){
                if ($fields == 'currently_insured' && !in_array($person,['aanvrager','partner'])) {
                    continue;
                }
                $header[] = $person . '.' . $fields;
            }

        }

        return $header;
    }

}