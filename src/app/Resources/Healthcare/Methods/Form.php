<?php

namespace App\Resources\Healthcare\Methods;


use App\Helpers\Healthcare2018Helper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Listeners\Resources2\OptionsListener;
use App\Models\Resource;
use App\Resources\Healthcare\HealthcareAbstractRequest;
use ArrayObject;
use Carbon\Carbon;
use DB, DateTime, Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Listeners\Resources2\RestListener;
use Komparu\Input\Contract\Validator;
use Komparu\Value\ValueInterface;

class Form extends HealthcareAbstractRequest
{
    protected $cacheDays = 0;

    const IGNORE_DEFAULTS = ['geboortedatum', 'geslacht', 'ingangsdatum'];

    const PERSONMAP = [
        'applicant'         => 'aanvrager',
        'applicant_partner' => 'partner',
        'child'             => 'kinderen',
        'aanvrager'         => 'applicant',
        'partner'           => 'applicant_partner',
        'child0'            => 'kinderen.0',
        'child1'            => 'kinderen.1',
        'child2'            => 'kinderen.2',
        'child3'            => 'kinderen.3',
        'child4'            => 'kinderen.4',
        'child5'            => 'kinderen.5',
        'child6'            => 'kinderen.6',
        'kinderen'          => 'child',
    ];

    const CURRENTLY_INSURED_FIELDS = [
        "aanvrager"  => [
            "aanvrager.heading",
            "aanvrager.title",
            "applicant.currently_insured",
            "aanvrager.voorletters",
            "aanvrager.tussenvoegsel",
            "aanvrager.achternaam",
            "hoofdadres.heading",
            "hoofdadres.straat",
            "hoofdadres.woonplaats",
            "hoofdadres.postcode",
            "hoofdadres.huisnummer",
            "hoofdadres.huisnummertoevoeging",
            "hoofdadres.telefoonnummer",
            "hoofdadres.emailAdres",
            "aanvrager.account_number",
            "iak_funnel_disclaimer"
        ],
        "partner"    => [
            "partner.heading",
            "partner.title",
            "applicant_partner.currently_insured",
            "partner.voorletters",
            "partner.tussenvoegsel",
            "partner.achternaam",
            "hoofdadres.heading",
            "hoofdadres.straat",
            "hoofdadres.woonplaats",
            "hoofdadres.postcode",
            "hoofdadres.huisnummer",
            "hoofdadres.huisnummertoevoeging",
            "hoofdadres.telefoonnummer",
            "hoofdadres.emailAdres",
            "partner.account_number",
            "iak_funnel_disclaimer"
        ],
        "kinderen.0" => [
            "kinderen.0.heading",
            "kinderen.0.title",
            "kinderen.0.voorletters",
            "kinderen.0.tussenvoegsel",
            "kinderen.0.achternaam",
            "kinderen.0.bsn",
        ],
        "kinderen.1" => [
            "kinderen.1.heading",
            "kinderen.1.title",
            "kinderen.1.voorletters",
            "kinderen.1.tussenvoegsel",
            "kinderen.1.achternaam",
            "kinderen.1.bsn",
        ],
        "kinderen.2" => [
            "kinderen.2.heading",
            "kinderen.2.title",
            "kinderen.2.voorletters",
            "kinderen.2.tussenvoegsel",
            "kinderen.2.achternaam",
            "kinderen.2.bsn",
        ],
        "kinderen.3" => [
            "kinderen.3.heading",
            "kinderen.3.title",
            "kinderen.3.voorletters",
            "kinderen.3.tussenvoegsel",
            "kinderen.3.achternaam",
            "kinderen.3.bsn",
        ],
        "kinderen.4" => [
            "kinderen.4.heading",
            "kinderen.4.title",
            "kinderen.4.voorletters",
            "kinderen.4.tussenvoegsel",
            "kinderen.4.achternaam",
            "kinderen.4.bsn",
        ],
        "kinderen.5" => [
            "kinderen.5.heading",
            "kinderen.5.title",
            "kinderen.5.voorletters",
            "kinderen.5.tussenvoegsel",
            "kinderen.5.achternaam",
            "kinderen.5.bsn",
        ],
        "kinderen.6" => [
            "kinderen.6.heading",
            "kinderen.6.title",
            "kinderen.6.voorletters",
            "kinderen.6.tussenvoegsel",
            "kinderen.6.achternaam",
            "kinderen.6.bsn",
        ],
        "kinderen.7" => [
            "kinderen.7.heading",
            "kinderen.7.title",
            "kinderen.7.voorletters",
            "kinderen.7.tussenvoegsel",
            "kinderen.7.achternaam",
            "kinderen.7.bsn",
        ],
        "kinderen.8" => [
            "kinderen.8.heading",
            "kinderen.8.title",
            "kinderen.8.voorletters",
            "kinderen.8.tussenvoegsel",
            "kinderen.8.achternaam",
            "kinderen.8.bsn",
        ],
        "kinderen.9" => [
            "kinderen.9.heading",
            "kinderen.9.title",
            "kinderen.9.voorletters",
            "kinderen.9.tussenvoegsel",
            "kinderen.9.achternaam",
            "kinderen.9.bsn",
        ],

    ];

    protected $defaultOverload = [
        'aanvrager.heading'        => [ResourceInterface::STYLE => 'applicant'],
        'partner.heading'          => [ResourceInterface::STYLE => 'partner'],
        'kinderen.0.heading'       => [ResourceInterface::STYLE => 'child'],
        'kinderen.1.heading'       => [ResourceInterface::STYLE => 'child'],
        'kinderen.2.heading'       => [ResourceInterface::STYLE => 'child'],
        'kinderen.3.heading'       => [ResourceInterface::STYLE => 'child'],
        'kinderen.4.heading'       => [ResourceInterface::STYLE => 'child'],
        'kinderen.5.heading'       => [ResourceInterface::STYLE => 'child'],
        'kinderen.6.heading'       => [ResourceInterface::STYLE => 'child'],
        'kinderen.7.heading'       => [ResourceInterface::STYLE => 'child'],
        'aanvrager.geboortedatum'  => [ResourceInterface::DISABLED => true],
        'partner.geboortedatum'    => [ResourceInterface::DISABLED => true],
        'kinderen.0.geboortedatum' => [ResourceInterface::DISABLED => true],
        'kinderen.1.geboortedatum' => [ResourceInterface::DISABLED => true],
        'kinderen.2.geboortedatum' => [ResourceInterface::DISABLED => true],
        'kinderen.3.geboortedatum' => [ResourceInterface::DISABLED => true],
        'kinderen.4.geboortedatum' => [ResourceInterface::DISABLED => true],
        'kinderen.5.geboortedatum' => [ResourceInterface::DISABLED => true],
        'kinderen.6.geboortedatum' => [ResourceInterface::DISABLED => true],
        'kinderen.7.geboortedatum' => [ResourceInterface::DISABLED => true],
    ];

    private $paramsForZorgweb = [];
    private $currentlyInsuredParams = [];
    private $mutations = [];

    public function setParams(Array $params)
    {
        //TODO: Add the parameter cleanup from the helper here
        //(applicant -> aanvrager etc).
        $this->params           = $this->getMappedParams($params);
        $this->paramsForZorgweb = array_only($this->params, ['aanvrager', 'partner', 'kinderen']);
        if(isset($this->paramsForZorgweb['kinderen'])){
            //Zorgweb does not allow children on both partners.
            //Put all the children on the product of the first child
            $product_id = head($this->paramsForZorgweb['kinderen'])['aanTeVragenPakketId'];
            foreach($this->paramsForZorgweb['kinderen'] as $index => $child){
                $this->paramsForZorgweb['kinderen'][$index]['aanTeVragenPakketId'] = $product_id;
            }
        }

        foreach($this->paramsForZorgweb as $personName => $person){
            $komparuPersonName = self::PERSONMAP[$personName];

            if(isset($this->params[$komparuPersonName][ResourceInterface::CURRENTLY_INSURED]) && $this->params[$komparuPersonName][ResourceInterface::CURRENTLY_INSURED] == true){
                //The person is currently insured
                $currentlyInsuredParams[$personName] = $this->paramsForZorgweb[$personName];
                unset($this->paramsForZorgweb[$personName]);
                if(isset($this->paramsForZorgweb['kinderen'])){
                    //Move the children out of zorgweb because iak will deal with it
                    foreach($this->paramsForZorgweb['kinderen'] as $index => $child){
                        $date    = Carbon::parse($this->params['child' . $index][ResourceInterface::BIRTHDATE]);
                        $newYear = Carbon::create(2018, 1, 1, 0, 0, 0);
                        if($date->diffInYears($newYear) >= 18){
                            //We have an adult child with a parent that is currently insured.
                            //Throw error because the "kid" should go through the funnel on its own
                            $this->setErrorString('Children over 18 and already insured');
                            return;
                        }
                        $this->params['child' . $index][ResourceInterface::CURRENTLY_INSURED] = true;
                        array_set($currentlyInsuredParams, 'kinderen.' . $index, $this->paramsForZorgweb['kinderen'][$index]);
                        unset($this->paramsForZorgweb['kinderen'][$index]);
                    }
                }
            }else{
                if($personName == 'partner'){
                    if( ! isset($this->paramsForZorgweb['aanvrager'])){
                        //The partner is not currently insured but the aanvrager is and was removed from the zorgweb
                        //parameters so move the partner to take the position of the aanvrager.
                        $this->paramsForZorgweb['aanvrager'] = $this->paramsForZorgweb[$personName];
                        unset($this->paramsForZorgweb[$personName]);
                        $this->mutations['aanvrager'] = 'partner';
                        if(isset($this->paramsForZorgweb['kinderen'])){
                            foreach($this->paramsForZorgweb['kinderen'] as $index => $child){
                                //Move the children out of zorgweb as iak will deal with them
                                array_set($currentlyInsuredParams, 'kinderen.' . $index, $this->paramsForZorgweb['kinderen'][$index]);
                                unset($this->paramsForZorgweb['kinderen'][$index]);
                            }
                        }
                    }
                }
            }
        }
    }

    public function executeFunction()
    {
        if( ! empty($this->params['no_overload'])){
            $overloadFormItems = [];
        }else{
            $overloadFormItems = $this->getOverloadFormItems();
        }

        $conditions = array_only($this->params, [ResourceInterface::WEBSITE, ResourceInterface::USER]);

        $formItems     = [];
        $zorgWebParams = $this->params;

        $personMap = self::PERSONMAP;
        foreach(Healthcare2018Helper::PERSONS as $personName){
            if(isset($this->params[$personName]['currently_insured'], $personMap[$personName]) && $this->params[$personName]['currently_insured'] == true){
                $zorgName    = $personMap[$personName];
                $outputArray = array_filter($overloadFormItems, function ($row) use ($zorgName) {
                    return isset($row[ResourceInterface::RESOURCE__ID]) && in_array($row[ResourceInterface::RESOURCE__ID], self::CURRENTLY_INSURED_FIELDS[$zorgName]);
                });
                $outputArray = array_map(function ($row) use ($zorgWebParams, $zorgName) {
                    if(isset($this->params[$row[ResourceInterface::RESOURCE__ID]])){
                        $row[ResourceInterface::DEFAULT_VALUE] = $this->params[$row[ResourceInterface::RESOURCE__ID]];
                    }
                    $row['enabled'] = true;
                    return self::processLabel($row, $zorgWebParams);
                }, $outputArray);
                $formItems   = array_merge($formItems, $outputArray);
            }
        }

        if(isset($this->paramsForZorgweb['kinderen']) && ! count($this->paramsForZorgweb['kinderen'])){
            unset($this->paramsForZorgweb['kinderen']);
        }

        $productId = array_get($this->params, 'applicant.product_id');
        if($productId){
            $zorgWebParams['applicant_company_name'] = Cache::rememberForever('product_company_name_' . $productId, function () use ($productId) {
                $product = ResourceHelper::callResource2('product.healthcare2018', [], RestListener::ACTION_SHOW, $productId);
                if($product){
                    return array_get($product, 'company.name');
                }
                return "";
            });
        }
        $productId = array_get($this->params, 'applicant_partner.product_id');
        if($productId){
            $zorgWebParams['applicant_partner_company_name'] = Cache::rememberForever('product_company_name_' . $productId, function () use ($productId, $conditions) {
                $product = ResourceHelper::callResource2('product.healthcare2018', $conditions, RestListener::ACTION_SHOW, $productId);
                if($product){
                    return array_get($product, 'company.name');
                }
                return "";
            });
        }

        //Get the form items from zorgweb
        if( ! empty(array_only($this->paramsForZorgweb, ['aanvrager', 'partner', 'kinderen']))){
            $zorgwebFormItems      = ResourceHelper::callResource2('generate_form_2018.zorgweb', array_only($this->paramsForZorgweb, ['aanvrager', 'partner', 'kinderen']));
            $zorgwebFormItemsByKey = (new Collection($zorgwebFormItems))->keyBy(ResourceInterface::RESOURCE__ID);

            foreach($zorgwebFormItemsByKey as $resourceId => $zorgwebFormItem){
                $formItem = $zorgwebFormItem;

                if( ! isset($overloadFormItems[$formItem[ResourceInterface::RESOURCE__ID]])){

                    //This is a new entry from zorgweb
                    $formItem[ResourceInterface::ACTIVE]  = true;
                    $formItem[ResourceInterface::ENABLED] = true;

                    //handle order of details
                    if(ends_with($formItem[ResourceInterface::RESOURCE__ID], '-details')){
                        $parentResourceId = substr($formItem[ResourceInterface::RESOURCE__ID], 0, strlen($formItem[ResourceInterface::RESOURCE__ID]) - strlen('-details'));
                        if(isset($formItems[$parentResourceId])){
                            $formItem[ResourceInterface::ORDER] = $formItems[$parentResourceId][ResourceInterface::ORDER] + 50000;
                        }
                    }

                    //Try to store the output to the rest resource that holds the form values
                    $overloadFormItems[$formItem[ResourceInterface::RESOURCE__ID]] = $this->storeFormItem($formItem, ! empty($zorgWebParams['no_overload']), array_only($this->params, [ResourceInterface::USER, ResourceInterface::WEBSITE]));
                }

                // Merge zorgweb form item with stored item, if it exists
                if(isset($overloadFormItems[$formItem[ResourceInterface::RESOURCE__ID]])){
                    $overloadItem = $overloadFormItems[$formItem[ResourceInterface::RESOURCE__ID]];
                    $formItem     = array_merge($formItem, array_only($overloadItem, $overloadItem[ResourceInterface::OVERLOAD_FIELDS]));
                }

                $formItem[ResourceInterface::ENABLED] = true;
                cw($formItem);


                // unset some defaults
                if(isset($formItem[ResourceInterface::NAME]) && str_contains($formItem[ResourceInterface::NAME], self::IGNORE_DEFAULTS)){
                    unset($formItem[ResourceInterface::DEFAULT_VALUE]);
                }

                //hacks
                if(str_contains($formItem[ResourceInterface::RESOURCE__ID], 'geboortedatum')){
                    $formItem[ResourceInterface::DEFAULT_VALUE] = date("d-m-Y", strtotime($zorgwebFormItem[ResourceInterface::DEFAULT_VALUE]));
                }

                if(str_contains($formItem[ResourceInterface::RESOURCE__ID], 'verzekering.reden-en-ingangsdatum/afwijkende-ingangsdatum')){
                    $datetime                                   = new DateTime('tomorrow');
                    $formItem[ResourceInterface::DEFAULT_VALUE] = $datetime->format("d-m-Y");
                }

                // Remove prices from 'Betalingstermijn' dropdown
                if($formItem[ResourceInterface::RESOURCE__ID] === 'verzekering.betalingstermijn'){
                    foreach($formItem[ResourceInterface::OPTIONS] as $key => $value){
                        $formItem[ResourceInterface::OPTIONS][$key] = preg_replace('~^(.*)(\:.*)$~', '$1', $value);
                    }
                }
                if(preg_match('/kinderen.(\d).heading/', $formItem[ResourceInterface::RESOURCE__ID], $matches)){
                    if( ! isset($matches[1])){
                        continue;
                    }
                    $birthDateKey = 'kinderen.' . $matches[1] . '.geboortedatum';
                    if( ! ($zorgwebFormItemsByKey->offsetExists($birthDateKey))){
                        continue;
                    }
                    $formItem['label'] = 'Kind (' . $zorgwebFormItemsByKey->offsetGet($birthDateKey)['default_value'] . ')';
                }


                //if you have mutated something into this person name (split resource id by .)
                //mutate it back
                $personName = head(explode('.', $formItem['resource_id']));
                if(isset($this->mutations[$personName])){
                    foreach($formItem as $propertyName => $property){
                        if(is_string($property)){
                            $formItem[$propertyName] = str_replace($personName, $this->mutations[$personName], $property);
                        }
                        if($propertyName == 'order'){
                            $formItem[$propertyName] += 2000000;
                        }
                    }
                }
                //Set list
                $formItems[$formItem[ResourceInterface::RESOURCE__ID]] = $formItem;
            }

            // Add any missing 'force' items from overload
            foreach($overloadFormItems as $overloadFormItem){
                unset($overloadFormItem['__index'], $overloadFormItem['__type']);
                if( ! empty($overloadFormItem[ResourceInterface::FORCE]) && ! isset($formItems[$overloadFormItem[ResourceInterface::RESOURCE__ID]])){
                    $formItems[$overloadFormItem[ResourceInterface::RESOURCE__ID]] = $overloadFormItem;
                }
            }
        }
        //     dd($formItems);


        //only active, enabled, forced rowed rows
        $formItems = array_filter($formItems, function ($item) use ($zorgWebParams) {
            return self::checkVisible($item, $zorgWebParams);
        });


        $formItems = array_map(function ($item) use ($zorgWebParams, $formItems) {
            return self::applyConditions($item, $zorgWebParams, $formItems);
        }, $formItems);

        $formItems = array_map(function ($item) use ($zorgWebParams) {
            return self::processLabel($item, $zorgWebParams);
        }, $formItems);

        //take out conditional fields
        $formItems = array_filter($formItems, function ($item) use ($zorgWebParams) {
            return ! isset($item[ResourceInterface::ENABLED]) || $item[ResourceInterface::ENABLED];
        });

        $formItems = array_map(function ($item) {
            unset($item[ResourceInterface::ENABLED]);
            return $item;
        }, $formItems);

        // Sort by order
        $sorted = Collection::make(array_values($formItems))->sortBy('order')->values()->toArray();

        $this->result = $sorted;
    }


    /**
     * Check if this row should be visible, based on fields
     *
     * @param $item
     *
     * @param $input
     *
     * @return bool
     */
    private static function checkVisible($item, $input)
    {
        $visible = isset($item[ResourceInterface::ENABLED]) && $item[ResourceInterface::ENABLED];

        if(isset($item[ResourceInterface::ACTIVE])){
            $visible = ($visible && $item[ResourceInterface::ACTIVE]);
        }

        if(isset($item[ResourceInterface::FORCE])){
            $visible = ($visible || $item[ResourceInterface::FORCE]);
        }

        if(isset($input['_' . ResourceInterface::TAG])){
            $visible = ($visible && isset($item[ResourceInterface::TAG]) && ($item[ResourceInterface::TAG] == $input['_' . ResourceInterface::TAG]));
        }
        return $visible;
    }

    protected function getOverloadFormItems()
    {
        //Get the existing form items from database using the rest resource
        $formResource = Resource::where('name', 'form_items.healthcare2018')->firstOrFail();
        //Only get the item fields that are actually inputs
        $formInputs = [];
        foreach($formResource->fields as $field){
            $formInputs[] = $field->name;
        }

        // set the view
        $applicantCurrentlyInsured = (isset($this->params['applicant'][ResourceInterface::CURRENTLY_INSURED]) && $this->params['applicant'][ResourceInterface::CURRENTLY_INSURED] == true);
        $partnerCurrentlyInsured   = (isset($this->params['partner'][ResourceInterface::CURRENTLY_INSURED]) && $this->params['partner'][ResourceInterface::CURRENTLY_INSURED] == true);
        $view                      = 'default';
        if($applicantCurrentlyInsured){
            $view = 'applicant_currently_insured';
            if($partnerCurrentlyInsured){
                $view = 'both_currently_insured';
            }
        }else{
            if($partnerCurrentlyInsured){
                $view = 'partner_currently_insured';
            }
        }

        $this->params['view'] = $view;


        $items = ResourceHelper::callResource2('form_items.healthcare2018', [
                                                                                OptionsListener::OPTION_LIMIT => ValueInterface::INFINITE,
                                                                            ] + array_only($this->params, $formInputs));

        $itemsByResourceId = [];
        foreach($items as $item){
            if(empty($item[ResourceInterface::OVERLOAD_FIELDS]) && ! $item[ResourceInterface::FORCE]){
                continue;
            }
            $itemsByResourceId[$item[ResourceInterface::RESOURCE__ID]] = $item;
        }

        return $itemsByResourceId;
    }


    /**
     * Apply the conditions
     *
     *
     * Operators: is_visible, is_not_visible, ==, !=
     * Logic: or / and
     *
     * Actions
     * enabled, default_value
     *
     */
    private static function applyConditions($item, $input, $formItems)
    {
        if( ! isset($item[ResourceInterface::CONDITIONS])){
            return $item;
        }

        $conditionField = is_string($item[ResourceInterface::CONDITIONS]) ? json_decode($item[ResourceInterface::CONDITIONS], true) : $item[ResourceInterface::CONDITIONS];
        //set logic
        $logic      = isset($conditionField['logic']) ? $conditionField['logic'] : "and";
        $conditions = $conditionField['conditions'];


        $conditionsMet = [];
        foreach($conditions as $condition){
            //check if this condition is met
            if($condition['operator'] == 'is_visible'){
                $conditionsMet[] = isset($formItems[$condition['source']]);
                continue;
            }
            if($condition['operator'] == 'is_not_visible'){
                $conditionsMet[] = ! isset($formItems[$condition['source']]);
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
                $item[ResourceInterface::ENABLED] = $resultBoolean ? $actionValue : ! $actionValue;
            }

            if(($actionKey == ResourceInterface::DEFAULT_VALUE) && $resultBoolean){
                $item[ResourceInterface::DEFAULT_VALUE] = $actionValue;
            }
        }
        return $item;
    }


    private static function processLabel($item, $input)
    {
        if(isset($item[ResourceInterface::LABEL]) && preg_match('/{{(.+)}}/', $item[ResourceInterface::LABEL], $matches)){
            $key = $matches[1];
            if(array_key_exists($key, $input)){
                $item[ResourceInterface::LABEL] = str_replace($matches[0], $input[$key], $item[ResourceInterface::LABEL]);
            }
        }

        return $item;
    }

    protected function storeFormItem($item, $doNotSave = false, $conditions)
    {
        $item[ResourceInterface::OVERLOAD_FIELDS] = [
            ResourceInterface::__ID,
            ResourceInterface::ACTIVE,
            ResourceInterface::ENABLED,
            ResourceInterface::ORDER,
            ResourceInterface::FORCE,
            ResourceInterface::TYPE,
            ResourceInterface::STYLE,
            ResourceInterface::TAG,
            ResourceInterface::CONDITIONS,
            ResourceInterface::DISABLED,
            ResourceInterface::OPTIONS,
        ];

        if(isset($this->defaultOverload[$item[ResourceInterface::RESOURCE__ID]])){
            $item = array_merge($item, $this->defaultOverload[$item[ResourceInterface::RESOURCE__ID]]);
        }

        $item[ResourceInterface::__ID] = $item[ResourceInterface::RESOURCE__ID];

        if(isset($item[ResourceInterface::NAME])){
            $find   = array_only($item, [ResourceInterface::NAME]);
            $result = ResourceHelper::callResource2('form_items.healthcare2018', $find);
            if(count($result) && isset($item[ResourceInterface::NAME])){
                Log::warning("Trying to store a new form_items.healthcare2018 entry, while name is already there: `" . $item[ResourceInterface::NAME] . '`');
                return $item;
            }else if(count($result)){
                Log::warning("Trying to store a new form_items.healthcare2018 entry, while name is empty");
                return $item;
            }
        }

        if( ! $doNotSave){
            ResourceHelper::callResource2('form_items.healthcare2018', $item, RestListener::ACTION_STORE);
            $item = ResourceHelper::callResource2('form_items.healthcare2018', $conditions, RestListener::ACTION_SHOW, $item['__id']);
        }

        return $item;
    }
}