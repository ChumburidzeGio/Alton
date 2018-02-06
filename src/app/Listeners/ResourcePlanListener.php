<?php

namespace App\Listeners\Resources2;

use App;
use App\Exception\InvalidResourceInput;
use App\Helpers\FactoryHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use Event;
use GuzzleHttp\Client;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Pool;
use GuzzleHttp\Promise\EachPromise;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Komparu\Value\Type;
use Komparu\Value\ValueInterface;
use Psr\Http\Message\ResponseInterface;

class ResourcePlanListener
{
    // Tick this version number up to invalidate cached plans
    protected static $planVersionNr = 22;

    // Default (sub)request timeout in sec
    const DEFAULT_REQUEST_TIMEOUT = 29;

    // These fields are always mapped to child-resources
    protected static $defaultAutomaticMapping = [
        'website' => 'website',
        'user' => 'user',
        'active' => 'active',
        'enabled' => 'enabled',
        '_limit' => '_limit',
        '_use_plan' => '_use_plan',
        'debug' => 'debug',
        'skipcache' => 'skipcache',
    ];

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process.propagated', [$this, 'process']);
    }

    public static function process(\App\Models\Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        // Only continue if we intend to use 'planned' resource processing
        if (empty($input[OptionsListener::OPTION_USE_PLAN]))
            return null;

        if (!empty($input['debug']))
            $plan = self::discoverPlan($resource, $action);
        else
            $plan = self::getPlan($resource, $action);

        if (!empty($input['show_plan'])) {
            header('Content-type: application/json');
            echo(json_encode($plan));
            exit;
        }

        $result = self::executePlan($plan, $input, $action, $id);

        if (!empty($input['show_plan_result'])) {
            header('Content-type: application/json');
            echo(json_encode(['result' => $result, 'plan' => $plan]));
            exit;
        }

        return $result;
    }

    public static function getPlan(\App\Models\Resource $resource, $action)
    {
        $planKey = 'resource2_plan_v'. self::$planVersionNr .'_' . md5($resource->name . '-'. $resource->updated_at) .'_'. $action;
        return Cache::rememberForever($planKey, function() use ($resource, $action) {
            return self::discoverPlan($resource, $action);
        });
    }

    public static function discoverPlan(\App\Models\Resource $resource, $action = 'index')
    {
        cws('discover_plan_' . $resource->name, 'Discovering plan for ' . $resource->name);

        $info = self::infoSRP($resource, $action);

        $isCollection = $resource->act_as == Resource::ACT_AS_COLLECTION || (in_array($resource->act_as, [Resource::ACT_AS_REST, Resource::ACT_AS_ELOQUENT_REST, Resource::ACT_AS_SERVICE_REST]) && ($action === RestListener::ACTION_INDEX || $action === RestListener::ACTION_BULK));

        $plan = [];
        $plan[] = [
            'action' => 'callResource1',
            'resource' => $resource->name,
            'outputKey' => $resource->name,
            'inputMappingSource' => '__input__',
            'inputMapping' => [],
            'resourceActAs' => $resource->act_as,
            'isCollection' => $isCollection,
            'inputDefinitions' => self::findFieldInputDefinitions($resource),
            'outputDefaults' => self::findFieldOutputDefaults($resource),
        ];

        foreach (self::findSplitFields($resource) as $field) {
            $plan[] = [
                'action' => 'splitByInputField',
                'resource' => $resource->name,
                'outputKey' => $resource->name,
                'splitField' => $field,
            ];
        }

        // Collect all child resource mapping information
        $callResourceSteps = [];
        $mergeResourceSteps = [];
        $nrParallel = 0;
        if(isset($info['after']) && count($info['after']) > 0) {
            foreach ($info['after'] as $alias => $child) {

                $mayBeParallel = $resource->hasBehaviour(Resource::BEHAVIOUR_PARALLEL) && $child['resource']->hasBehaviour(Resource::BEHAVIOUR_PARALLEL_CHILD);
                if ($mayBeParallel)
                    $nrParallel++;

                if (!is_array(array_get($info['mapping'], $child['resource']->id))) {
                    // Having no mapping is currently 'valid' - it assumes to map __id to __id of child products. (backward compatiblity)
                    //    throw new \Exception('Error in planning `'. $resource->name .'`: cannot map to `'. $child['resource'] .'`, no \'to\' fields set on `'. $resource->name .'`.');
                }

                $callResourceSteps[$child['resource']->name] = [
                    'action' => $mayBeParallel ? 'prepareParallelResource' : 'callResource2',
                    'resource' => $child['resource']->name,
                    'outputKey' => $child['resource']->name,
                    'inputMappingSource' => $resource->name,
                    // Always map 'website' and 'user' (hacky?)
                    'inputMapping' => array_merge(self::$defaultAutomaticMapping, array_get($info['mapping'], $child['resource']->id, [])),
                    'inputMappingActions' => self::findInputMappingActions($child['resource']),
                    'parallelChunkByFields' => self::findChunkByFields($child['resource']),
                    'outputDefaults' => self::findFieldOutputDefaults($child['resource']),
                    'behaviours' => $child['resource']->behaviours,
                ];

                $fieldsOfInterest = self::findFieldsOfInterest($resource, $child['resource']);

                if ($fieldsOfInterest['mustMatch'] === [])
                    throw new \Exception('Sub-resource `'. $child['resource']->name .'` has no mapping to `'. $resource->name .'` - mustMatch is empty.');

                $mergeResourceSteps[$child['resource']->name] = [
                    'action' => 'merge',
                    'destination' => $resource->name,
                    'source' => $child['resource']->name,
                    'mustMatch' => $fieldsOfInterest['mustMatch'],
                    'merge' => $fieldsOfInterest['merge'],
                    'order' => $fieldsOfInterest['order'],
                    'outputKey' => $resource->name,
                ];
            }
        }

        // Determine child resource inter-dependencies
        foreach ($callResourceSteps as $resourceName => $step)
        {
            foreach ($step['inputMapping'] as $to => $from) {
                foreach ($mergeResourceSteps as $mergeResourceName => $mergeStep) {
                    if (isset($mergeStep['merge'][$from]) && $resourceName != $mergeResourceName) {
                        // For now, we ignore 'own_risk' in carinsurance
                        if ($from == ResourceInterface::OWN_RISK)
                            continue;

                        // We *could* actually try to resolve this, by executing the dependant resources & merges first. But nothing so far requires this logic.
                        // (so we leave that code unwritten until needed)
                        throw new \Exception('Sub-resource `'. $resourceName .'` is input-mapping dependant on sub-resource `'. $mergeResourceName .'` via field `'. $from .'`.');
                    }
                }
            }
        }
        foreach ($mergeResourceSteps as $resourceName => $step)
        {
            foreach ($step['mustMatch'] as $from => $to) {
                foreach ($mergeResourceSteps as $mergeResourceName => $mergeStep) {
                    if ($mergeResourceName != $resourceName && isset($mergeStep['merge'][$from])) {
                        // We *could* actually try to resolve this, by executing the dependant resources & merges first. But nothing so far requires this logic.
                        // (so we leave that code unwritten until needed)
                        throw new \Exception('Sub-resource `'. $resourceName .'` is must-match dependant on sub-resource `'. $mergeResourceName .'` via field `'. $from .'`.');
                    }
                }
            }
        }

        // Just call all the resource (unordered)
        $plan = array_merge($plan, array_values($callResourceSteps));
        if ($nrParallel > 0)
            $plan[] = ['action' => 'executeParallelCalls'];

        // Determine order of merges
        $fieldMergeOrder = [];
        foreach ($mergeResourceSteps as $resourceName => $step) {
            foreach ($step['merge'] as $to => $from) {
                $fieldMergeOrder[$to][(int)$step['order'][$to]][$resourceName] = $from;
            }
        }
        // Place merges in buckets that preserve order of the fields
        $mergeBuckets = [];
        foreach ($fieldMergeOrder as $field => $merges) {
            $bucketNr = 0;
            if (count($merges) > 1) {
                // Make sure we're sorted by reverse order - highest->low, so that the lowest order has the last chance to write the field.
                krsort($merges);
                $fieldMergeOrder[$field] = $merges;
            }
            foreach ($merges as $fieldMerges) {

                // We look to find the first bucket for our resource, going from our current order
                for (; $bucketNr < count($mergeBuckets); $bucketNr++) {
                    foreach ($fieldMerges as $fromResource => $fromField) {
                        if ($mergeBuckets[$bucketNr]['source'] == $fromResource) {
                            $mergeBuckets[$bucketNr]['merge'][$field] = $fromField;
                            unset($fieldMerges[$fromResource]);
                            break;
                        }
                    }
                    if (count($fieldMerges) == 0) {
                        break;
                    }
                }
                // For eache resource we did not find a bucket for, we add one (or more) at the end
                foreach ($fieldMerges as $fromResource => $fromField) {
                    // We copy the merge steps we created before
                    $mergeBuckets[$bucketNr] = $mergeResourceSteps[$fromResource];
                    // Forget all existing merges, and add just the current one
                    $mergeBuckets[$bucketNr]['merge'] = [$field => $fromField];
                    // We do not need the 'order' info anymore
                    unset($mergeBuckets[$bucketNr]['order']);
                    $bucketNr++;
                }
            }
        }
        // Sanity check: Validate that our merges preserved order.
        foreach ($fieldMergeOrder as $field => $mergeGroups) {
            $lastBucketNr = -1;
            foreach ($mergeGroups as $orderNr => $merges) {
                foreach ($mergeBuckets as $nr => $bucket) {
                    foreach ($merges as $fromResource => $fromField) {
                        if ($bucket['source'] == $fromResource && isset($bucket['merge'][$field])) {
                            unset($merges[$fromResource]);

                            if (count($merges) == 0) {
                                if ($lastBucketNr > $nr)
                                    throw new \Exception('Merge bucket invalid! Merge `' . $field . '` resource `' . $fromResource . '` is in incorrect order.');
                                $lastBucketNr = $nr;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $plan = array_merge($plan, $mergeBuckets);

        // Run scripts on final item, if needed
        $scripts = self::convertFieldScripts($resource);
        if ($scripts) {
            $plan[] = [
                'action' => 'runScripts',
                'source' => $resource->name,
                'scripts' => $scripts,
                'outputKey' => $resource->name,
            ];
        }

        $plan[] = [
            'action' => 'returnResource',
            'outputKey' => $resource->name,
            'resourceActAs' => $resource->act_as,
            'outputIsStrict' => (bool)$resource->strict,
            'isCollection' => $isCollection,
            'outputFieldDefinitions' => self::findFieldOutputDefinitions($resource),
            'inputFields' => array_keys(self::findFieldInputs($resource)),
        ];

        cwe('discover_plan_' . $resource->name);

        return $plan;
    }


    protected static function infoSRP(Resource $resource, $action)
    {
        $mapping = [];
        $from = [];
        $after = [];
        $rules = [];
        $filters = [];

        $resource->load(['fields', 'fields.resource', 'fields.fromFields.resource', 'fields.toFields.resource']);
        $resource->populateFields($action);
        foreach ($resource->fields as $field) {

            $name = $field->name;
            $rules[$name] = $field->rules;
            $filters[$name] = $field->filters;

            // We want to know to which resources we need to send data
            foreach ($field->toFields as $toField) {
                $alias = $toField->resource->id;
                $mapping[$alias][$toField->name] = $name;
            }

            // We want to know to which resources we need to send data
            foreach ($field->fromFields as $fromField) {
                $alias = $fromField->resource->id;
                $from[$alias]['id'] = null;
                $from[$alias]['action'] = 'index';
                $from[$alias]['resource'] = $fromField->resource;
                $from[$alias]['fields'][$fromField->name] = $field->name;
                $from[$alias]['mapping'] = array_key_exists($alias, $mapping) ? $mapping[$alias] : [];
            }

        }

        foreach ($from as $alias => $relation) {
            $after[$alias] = $relation;
        }

        // This is the info we want to return
        $result = compact('after', 'rules', 'filters', 'mapping');
        return $result;
    }

    private static function findFieldInputs(Resource $resource)
    {
        $fields = [];
        foreach ($resource->fields as $field){
            if ($field->input){
                $fields[$field->name] = $field;
            }
        }
        return $fields;
    }

    /**
     * Return field data relevant for input-processing. (currently defaults and type)
     *
     * @param Resource $resource
     * @return array
     */
    private static function findFieldInputDefinitions(Resource $resource)
    {
        $fields = [];
        foreach (self::findFieldInputs($resource) as $field){
            $fields[$field->name]['type'] = $field->type;
            if ($field->input_default !== null){
                $fields[$field->name]['default'] = self::castValueToType($field->input_default, ['type' => $field->type]);
            }
        }
        return $fields;
    }

    private static function findFieldOutputDefinitions(Resource $resource)
    {
        $fields = [];
        foreach ($resource->fields as $field){
            if ($field->output) {
                $fields[$field->name]['type'] = $field->type;
            }
        }

        // Expand 'array' types with sub_types
        foreach ($fields as $name => $field) {
            if ($field['type'] === Type::ARR) {
                $fields[$name]['sub_types'] = [];
                foreach ($fields as $subFieldName => $subField) {
                    if (starts_with($subFieldName, $name .'.')) {
                        $fields[$name]['sub_types'][substr($subFieldName, strlen($name .'.'))] = $subField;
                        unset($fields[$subFieldName]);
                    }
                }
            }
        }

        return $fields;
    }

    private static function findFieldOutputDefaults(Resource $resource)
    {
        $defaults = [];
        foreach ($resource->fields as $field){
            if ($field->output) {
                $defaults[$field->name] = self::castValueToType($field->value, ['type' => $field->type]);
            }
        }

        // Forget any 'array' defaults, we can't set them
        foreach ($resource->fields as $field){
            if ($field->output && $field->type == Type::ARR) {
                foreach ($defaults as $k => $v) {
                    if (starts_with($k, $field->name .'.')) {
                        unset($defaults[$k]);
                    }
                }
            }
        }

        return $defaults;
    }

    private static function findInputMappingActions(Resource $resource)
    {
        $mappingActions = [];
        foreach ($resource->fields as $field){
            if (in_array(Field::FILTER_COMMA_SEPARATED_INPUT_ALLOWED, $field->filters))
                $mappingActions[$field->name] = [Field::FILTER_COMMA_SEPARATED_INPUT_ALLOWED];
        }
        return $mappingActions;
    }

    private static function castValueToType($value, $definition)
    {
        if ($value === null)
            return $value;

        switch ($definition['type']) {
            case Type::INTEGER:
            case Type::PRICECENT:
                //Correct the derpiness of the Document package with regards to the infinity symbol
                //(Document package replaces 999999999 with INFINITY_SYMBOL)
                if(is_string($value) && $value === ValueInterface::INFINITY_SYMBOL) return 999999999;
                return is_scalar($value) ? (int)$value : null;
            case Type::PRICE:
            case Type::DECIMAL:
            case Type::FLOAT:
            case Type::RATING:
                //Correct the derpiness of the Document package with regards to the infinity symbol
                //(Document package replaces 999999999 with INFINITY_SYMBOL)
                if(is_string($value) && $value === ValueInterface::INFINITY_SYMBOL) return 999999999.00;
                return is_scalar($value) ? (float)$value : null;
            case Type::STRING:
            case Type::SHORTSTRING:
            case Type::TEXT:
            case Type::CHOICE:
            case Type::IMAGE:
            case Type::HEADING:
            case Type::LICENSEPLATE:
            case Type::URL:
                return is_scalar($value) ? (string)$value : null;
            case Type::DATE:
                return is_scalar($value) ? (string)$value : null;
            case Type::BOOLEAN:
                return is_scalar($value) ? (bool)$value : null;
            case Type::ARR:
            case Type::OBJECT:
                try{
                    $value = json_decode($value);
                }catch(\Exception $ex){
                    throw new \Exception('Column has invalid json.');
                }
                return $value;
            default:
                return $value;
        }
    }


    private static function findSplitFields(Resource $resource)
    {
        $fields = [];
        foreach ($resource->fields as $field){
            if (in_array( Field::FILTER_SPLIT, $field->filters))
                $fields[] = $field->name;
        }
        return $fields;
    }

    private static function findChunkByFields(Resource $resource)
    {
        $fields = [];
        foreach ($resource->fields as $field){
            if (in_array( Field::FILTER_USE_FOR_CHUNKING, $field->filters))
                $fields[] = $field->name;
        }
        return $fields;
    }


    private static function convertFieldScript($script, $allowNull)
    {
        $vars = [];
        $converted = preg_replace_callback('~[\'"]?\{([^\}]+)\}[\'"]?~', function ($matches) use (&$vars) {
            $vars[] = $matches[1];
            return 'array_get($i, \''. addslashes($matches[1]) .'\')';
        }, $script);

        if ($allowNull && count($vars)) {
            $if = [];
            foreach ($vars as $var) {
                $if[] = 'is_null(array_get($i, \''. addslashes($var) .'\'))';
            }
            $converted = '('. implode(' || ', $if) .'? null : ('. $converted .'))';
        }

        $f = false;
        try {
            // Trying to catch this without a fatal error does not seem to work right now :/
            // Some error/shutdown handler is messing things up.
            $f = create_function('$i', 'return ('. $converted ."\n" .');');
        }
        catch (\Exception $e) {
            throw new \Exception('Could not parse field script: '. $e->getMessage(), 0, $e);
        }

        if (!$f) {
            $lastError = error_get_last();
            throw new \Exception('Could not parse field script: ' . $lastError['message'] . ' - ' . $converted);
        }

        return $converted;
    }

    private static function convertFieldScripts(Resource $resource)
    {
        $scripts = [];
        foreach ($resource->fields as $field) {
            if ($field->script != null){

                if (count($field->toFields) > 0)
                    throw new \Exception('Cannot have both a `to` mapping and a `script` on one field on resource `'. $resource->name .'` field `'. $field->name .'`.');

                $scripts[$field->name] = self::convertFieldScript($field->script, in_array(Field::FILTER_ALLOW_NULL, $field->filters));
            }
        }
        return $scripts;
    }

    private static function findFieldsOfInterest(Resource $parent, Resource $child)
    {
        $fieldsOfInterest['merge'] = [];
        $fieldsOfInterest['mustMatch'] = [];

        foreach ($parent->fields as $field){
            foreach ($field->fromFields as $fromField){
                if ($fromField->resource->name == $child->name){
                    if (in_array(Field::FILTER_MERGE_FULL_RESOURCE, $field->filters))
                        $fieldsOfInterest['merge'][$field->name] = '*';
                    else
                        $fieldsOfInterest['merge'][$field->name] = $fromField->name;

                    $fieldsOfInterest['order'][$field->name] = $fromField->order;
                }
            }

            foreach ($field->toFields as $toField){
                if ($toField->resource->name == $child->name && in_array($toField->strategy, [Field::STRATEGY_PRIMARY_KEY, Field::STRATEGY_AUTO_INCREMENTED_PRIMARY_KEY])){
                    $fieldsOfInterest['mustMatch'][$field->name] = $toField->name;
                    $fieldsOfInterest['order'][$field->name] = $toField->order;
                }
            }
        }

        // No field matching? Default to matching __id to __id
        if ($fieldsOfInterest['mustMatch'] === []) {
            $fieldsOfInterest['mustMatch']['__id'] = '__id';
            $fieldsOfInterest['order']['__id'] = 1;
        }

        return $fieldsOfInterest;
    }

    // Execution methods:

    public static function executePlan(array $plan, ArrayObject $input, $action = 'index', $id = null)
    {
        $isDebugging = !empty($input['debug']);

        $planHash = md5(serialize($plan));

        $data = [
            '__input__' => $input->getArrayCopy(),
            '__action__' => $action,
            '__id__' => $id,
        ];
        $noPropagation = !empty($data['__input__']['_no_propagation']);

        $result = [];
        foreach ($plan as $nr => $step)
        {
            $description = 'Executing step `'. $step['action'] .'` - '. array_get($step, 'resource', array_get($step, 'source'));
            $extraDescription = '';

            cws('execute_plan_step_'. $planHash .'_'. $nr, $description);
            if ($step['action'] == 'callResource1') {
                $data[$step['outputKey']] = self::callResource1($step, $data);
                $extraDescription = ': '. count($data[$step['outputKey']]) .' fetched';
            }
            else if ($step['action'] == 'callResource2' && !$noPropagation) {
                $data[$step['outputKey']] = self::callResource2($step, $data);
                $extraDescription = ': '. count($data[$step['outputKey']]) .' results';
            }
            else if ($step['action'] == 'prepareParallelResource' && !$noPropagation) {
                $data[$step['outputKey']] = self::prepareParallelResource($step, $data);
            }
            else if ($step['action'] == 'executeParallelCalls' && !$noPropagation) {
                $data = self::executeParallelCalls($step, $data);
            }
            else if ($step['action'] == 'merge' && !$noPropagation) {
                $info = [];
                $data[$step['outputKey']] = self::merge($step, $data, $info);
                $extraDescription = ': '. json_encode($info);
            }
            else if ($step['action'] == 'runScripts') {
                $data[$step['outputKey']] = self::runScripts($step, $data);
            }
            else if ($step['action'] == 'splitByInputField') {
                $data[$step['outputKey']] = self::splitByInputField($step, $data);
                $extraDescription = ': '. count($data[$step['outputKey']]) .' results';
            }
            else if ($step['action'] == 'returnResource') {
                $data[$step['outputKey']] = self::returnResource($step, $data);
                $result = $data[$step['outputKey']];
                $extraDescription = ': '. count($data[$step['outputKey']]) .' results';
            }
            if ($isDebugging) {
                cw($description . $extraDescription);
                cw(['step' => $step]);
                cw($data);
            }
            cwe('execute_plan_step_'. $planHash .'_'. $nr, $description . $extraDescription);
        }

        return $result;
    }

    private static function returnResource(array $step, array $resourceData)
    {
        if (!isset($resourceData[$step['outputKey']]))
            throw new \Exception('Expected resource data `'. $step['outputKey'] .'` to be present.');

        // Todo: This can be done a bit cleaner & a lot faster, if there were no input fields in this result, or if we split input/output/unknown fields.

        $unsetFields = [];

        // If we're not being strict, still filter out any input fields that are around
        if (!$step['outputIsStrict']) {
            $dotOutputs = [];
            foreach ($step['outputFieldDefinitions'] as $field => $definition)
                if (str_contains($field, '.'))
                    $dotOutputs[] = explode('.', $field)[0];
            $unsetFields = array_diff(array_keys($resourceData['__input__']), array_keys($step['outputFieldDefinitions']), $dotOutputs);
        }

        // Respect the '_visible' option and do not return those fields as `null` when not present
        if (isset($resourceData['__input__'][OptionsListener::OPTION_VISIBLE])) {
            $visibleFields = $resourceData['__input__'][OptionsListener::OPTION_VISIBLE];
            if (!is_array($visibleFields))
                $visibleFields = explode(',', (string)$visibleFields);

            $unsetFields = array_merge($unsetFields, array_diff(array_keys($step['outputFieldDefinitions']), $visibleFields));
        }
        // Respect the '_visible' option, when set via permissions
        if (isset($resourceData['__input__'][OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_VISIBLE])) {
            $visibleFields = $resourceData['__input__'][OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_VISIBLE];
            if (!is_array($visibleFields))
                $visibleFields = explode(',', (string)$visibleFields);

            $unsetFields = array_merge($unsetFields, array_diff(array_keys($step['outputFieldDefinitions']), $visibleFields));
        }

        $data = $resourceData[$step['outputKey']];

        $output = [];
        foreach ($data as $nr => $row) {
            $output[$nr] = $step['outputIsStrict'] ? [] : $row;

            foreach ($step['outputFieldDefinitions'] as $field => $definition) {
                if (in_array($field, $unsetFields))
                    continue;
                if (!str_contains($field, '.'))
                    $output[$nr][$field] = self::castValueToType(isset($row[$field]) ? $row[$field] : null, $definition);
                else
                    array_set($output[$nr], $field, self::castValueToType(array_get($row, $field), $definition));
            }

            if (isset($unsetFields)) {
                foreach ($unsetFields as $field){
                    if (array_key_exists($field, $output[$nr])){
                        unset($output[$nr][$field]);
                    }
                }
            }
        }

        if ($step['isCollection']) {
            // Prepare rows for late modification through events
            $collection = new ArrayObject($output);
            $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $step['outputKey'], false, true);
            Event::fire('resource.collection.after', [$resource, new ArrayObject($resourceData['__input__']), $collection, new ArrayObject(), $resourceData['__action__'], $resourceData['__id__']]);
            Event::fire('resource.' . $resource->name . '.collection.after', [$resource, new ArrayObject($resourceData['__input__']), $collection, new ArrayObject(), $resourceData['__action__'], $resourceData['__id__']]);
            $output = $collection->getArrayCopy();

            return $output;
        }
        else {
            $output = head($output);

            // Prepare rows for late modification through events
            $row = new ArrayObject($output);
            $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $step['outputKey'], false, true);
            Event::fire('resource.row.after', [$resource, new ArrayObject($resourceData['__input__']), $row, $resourceData['__action__'], $resourceData['__id__']]);
            $output = $row->getArrayCopy();

            return $output;
        }
    }

    private static function runScripts(array $step, array $resourceData)
    {
        if (!isset($resourceData[$step['source']])){
            return [];
        }

        $sourceData = $resourceData[$step['source']];

        // Build script functions
        $scriptFunctions = [];
        foreach ($step['scripts'] as $field => $script) {
            $scriptFunctions[$field] = create_function('$i', 'return ' . $script . ';');

            if (!$scriptFunctions[$field])
                throw new \Exception('Error in making script for `'. $step['source'] .'.'. $field .'`: `'. $script .'`');
        }

        // Apply script functions to all rows
        foreach ($sourceData as $nr => $item) {
            foreach ($scriptFunctions as $field => $function) {
                try {
                    $sourceData[$nr][$field] = $function($sourceData[$nr]);
                }
                catch (\ErrorException $e) {
                    throw new \Exception('Error in script `'. $step['source'] .'.'. $field .'`: '. $e->getMessage() .' - '. json_encode($item) .' - '. $step['scripts'][$field], 0, $e);
                }
            }
        }

        return $sourceData;
    }

    private static function merge(array $step, array $resourceData, &$info = [])
    {
        if (!isset($resourceData[$step['source']])){
            return $resourceData[$step['destination']];
        }
        if (!isset($resourceData[$step['destination']])){
            return [];
        }

        $sourceData = $resourceData[$step['source']];
        $destinationData = $resourceData[$step['destination']];

        // Todo: add some optimalization for looping here? (always compare smallest with greatest?)
        foreach ($sourceData as $sourceRow) {
            $foundNr = 0;

            foreach ($destinationData as $rowNr => $destinationRow) {
                $found = true;
                foreach ($step['mustMatch'] as $destinationField => $sourceField) {
                    if (array_get($destinationRow, $destinationField) != array_get($sourceRow, $sourceField)) {

                        // Do some logging here? To debug why rows are being discarded
                        $found = false;
                        break;
                    }
                }

                if ($found) {
                    $foundNr++;
                    foreach ($step['merge'] as $toField => $fromField) {
                        if ($fromField === '*')
                            $value = $sourceRow;
                        else
                            $value = array_get($sourceRow, $fromField);
                        if ($value !== null)
                            array_set($destinationData[$rowNr], $toField, $value);
                    }
                    //cw('Row FOUND for merge '. $step['source'].' : '. array_get($sourceRow, 'resource') .' -'. array_get($sourceRow, 'resource.id'));
                    $info['matched'] = array_get($info, 'matched', 0) + 1;

                    // Multiple source rows can match one destination row, so we continue.
                    // (example: carinsurance_coverages, carinsurance_coverages_accessoires)
                    // Todo: Examine if we can specify that we only match one, and speed up merging by 'break'ing here for some resources.
                }
            }

            if (!$foundNr) {
                $info['not matched'] = array_get($info, 'not matched', 0) + 1;
            }
        }

        return $destinationData;
    }

    private static function callResource1(array $step, array $resourceData)
    {
        // Construct input
        $input = [];
        if (isset($resourceData[$step['inputMappingSource']]))
            foreach ($resourceData[$step['inputMappingSource']] as $key => $value)
                array_set($input, $key, $value);

        // Add any input defaults
        foreach ($step['inputDefinitions'] as $fieldName => $fieldDefinition)
            if (!array_has($input, $fieldName) && isset($fieldDefinition['default']))
                array_set($input, $fieldName, $fieldDefinition['default']);

        // Run the actual call
        $output = new ArrayObject();
        $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $step['resource'], false, true);

        Event::until('resource.process', [$resource, new ArrayObject($input), $output, $resourceData['__action__'], $resourceData['__id__']]);

        // Pretend 'single' resource is just a collection of one
        if (!$step['isCollection']) {
            if (isset($output[0]) && is_array($output[0]))
                throw new \Exception('Unexpected data rows for act-as-single resource: ' . count($output) .', expected 1.');
            if ($output instanceof ArrayObject)
                $output = $output->getArrayCopy();
            $output = [$output];
        }

        // Add input as output? (bit weird?)
        foreach ($output as $nr => $item) {
            foreach ($input as $field => $value) {
                if (array_get($output[$nr], $field) === null) {
                    if (isset($step['inputDefinitions'][$field]))
                        array_set($output[$nr], $field, self::castValueToType($value, $step['inputDefinitions'][$field]));
                    else
                        array_set($output[$nr], $field, $value);
                }
            }
        }

        // Set output defaults
        foreach ($output as $nr => $item) {
            foreach ($step['outputDefaults'] as $field => $default) {
                if (!array_has($output[$nr], $field)) {
                    array_set($output[$nr], $field, $default);
                }
            }
        }

        $inputObj = new ArrayObject($input);

        // Apply collection events
        if ($step['isCollection']) {
            $collection = new ArrayObject($output);
            Event::fire('resource.collection.before', [$resource, $inputObj, $collection, new ArrayObject(), $resourceData['__action__'], $resourceData['__id__']]);
            Event::fire('resource.collection.' . $resource->name . '.before', [$resource, $inputObj, $collection, new ArrayObject(), $resourceData['__action__'], $resourceData['__id__']]);
            $output = $collection->getArrayCopy();
        }

        // Apply row events
        foreach ($output as $nr => $item) {
            $itemObj = new ArrayObject($item);
            Event::fire('resource.row.before', [$resource, $itemObj, new ArrayObject(), $output, $inputObj, $nr, $resourceData['__action__']]);
            $output[$nr] = $itemObj->getArrayCopy();
        }

        return $output;
    }

    private static function mapInputs(array $step, $resourceData)
    {
        // Apply mapping
        if (!$step['inputMapping'] || !isset($resourceData[$step['inputMappingSource']]))
            throw new \Exception('We are mapping all? '. json_encode($step));

        $inputs = [];
        // Apply mapping
        foreach ($resourceData[$step['inputMappingSource']] as $sourceData) {
            $input = [];

            // Hardcoded 'skip if resource name does not match resource' :/
            // Todo: Need to make this a field tag or something...
            if (array_has($sourceData, ResourceInterface::RESOURCE_NAME) && isset($step['inputMapping'][ResourceInterface::RESOURCE_NAME])) {
                if (array_get($sourceData, ResourceInterface::RESOURCE_NAME) != $step['resource']) {
                    continue;
                }
            }
            // End hardcoded stuff

            foreach ($step['inputMapping'] as $toField => $fromField) {
                $newValue = array_get($sourceData, $fromField);

                // We do not process 'null' values as inputs
                if ($newValue === null)
                    continue;

                // Just set it.
                array_set($input, $toField, $newValue);
            }
            $inputs[] = $input;
        }

        return $inputs;
    }

    private static function mapMergedInputs(array $step, $resourceData)
    {
        // Apply mapping
        if (!$step['inputMapping'] || !isset($resourceData[$step['inputMappingSource']]))
            throw new \Exception('We are mapping all? '. json_encode($step));

        $input = [];
        foreach ($resourceData[$step['inputMappingSource']] as $sourceData) {

            // Hardcoded 'skip if resource name does not match resource' :/
            // Todo: Need to make this a field tag or something...
            if (array_has($sourceData, ResourceInterface::RESOURCE_NAME) && isset($step['inputMapping'][ResourceInterface::RESOURCE_NAME])) {
                if (array_get($sourceData, ResourceInterface::RESOURCE_NAME) != $step['resource']) {
                    continue;
                }
            }
            // End hardcoded stuff

            foreach ($step['inputMapping'] as $toField => $fromField) {
                $currentValue = array_get($input, $toField);
                $newValue = array_get($sourceData, $fromField);

                // We do not process 'null' values as inputs
                if ($newValue === null)
                    continue;

                if (isset($currentValue) && is_array($currentValue) && is_array($newValue)) {
                    // We got an existing input array and new value array? Merge them
                    array_set($input, $toField, array_merge($currentValue, $newValue));
                }
                else if (isset($currentValue) && is_array($currentValue)) {
                    // We got an existing input array? Add new value to it.
                    array_set($input, $toField, array_merge($currentValue, [$newValue]));
                }
                else if (isset($currentValue) && !is_array($currentValue) && $currentValue !== $newValue) {
                    // We got an existing input value, and the new one is different? Make input an array with both values.
                    array_set($input, $toField, [$currentValue, $newValue]);
                }
                else {
                    // Just set it.
                    array_set($input, $toField, $newValue);
                }
            }
        }

        foreach ($step['inputMappingActions'] as $key => $mappingActions) {
            if (in_array(Field::FILTER_COMMA_SEPARATED_INPUT_ALLOWED, $mappingActions)) {
                $value = array_get($input, $key);
                if (is_array($value))
                    array_set($input, $key, implode(',', $value));
            }
        }

        return $input;
    }

    private static function mergeInputArrays(array $inputs, array $inputMapping)
    {
        // Todo: merge duplicate logic in this function and `mapMergedInputs`, if possible (this one does not do mapping, just merging)

        $mergedInput = [];
        foreach ($inputs as $input) {
            foreach ($inputMapping as $toField => $fromField) {
                $currentValue = array_get($mergedInput, $toField);
                $newValue = array_get($input, $toField);

                // We do not process 'null' values as inputs
                if ($newValue === null)
                    continue;

                if (isset($currentValue) && is_array($currentValue)) {
                    // We got an existing input array? Add new value to it.
                    array_set($mergedInput, $toField, array_merge($currentValue, [$newValue]));
                }
                else if (isset($currentValue) && !is_array($currentValue) && $currentValue !== $newValue) {
                    // We got an existing input value, and the new one is different? Make input an array with both values.
                    array_set($mergedInput, $toField, [$currentValue, $newValue]);
                }
                else {
                    // Just set it.
                    array_set($mergedInput, $toField, $newValue);
                }
            }
        }

        return $mergedInput;
    }

    private static function callResource2(array $step, array $resourceData)
    {
        $input = self::mapMergedInputs($step, $resourceData);

        // Mapping present and no input? Assume it we got 0 input, and thus no results.
        if ($step['inputMapping'] && $input === []) {
            return [];
        }

        $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $step['resource'], false, true);
        $result = [];
        try
        {
            cw('Calling resource `'. $resource->name .'` internally: '. URL::route('resource.index', ['resource2' => $resource->name] + $input) );
            $result = ResourceHelper::call($resource, 'index', $input);
        }
        catch (App\Exception\ResourceError $e)
        {
            cw('Error: '. $e->getMessage());
        }
        catch (InvalidResourceInput $e)
        {
            cw('Error: '. $e->getMessage());
        }
        catch (\Exception $e)
        {
            cw('Error: '. $e->getMessage());
        }

        return $result;
    }


    private static function prepareParallelResource(array $step, array $resourceData)
    {
        $generalTimeout = (float)array_get($resourceData['__input__'], '__timeout', self::DEFAULT_REQUEST_TIMEOUT);
        $childRequestTimeouts = (array)array_get($resourceData['__input__'], OptionsListener::OPTION_RESOURCE_TIMEOUT, []);

        $requests = [];

        if (in_array(Resource::BEHAVIOUR_SPLITABLE, $step['behaviours'])) {

            $inputs = self::mapInputs($step, $resourceData);

            // Mapping present and no input? Assume it we got 0 input, and thus no results.
            if ($step['inputMapping'] && $inputs === []) {
                return [];
            }

            $chunks = array_chunk((array)$inputs, ceil(count($inputs) / 3));

            foreach ($chunks as $chunk) {
                $mergedInput = self::mergeInputArrays($chunk, $step['inputMapping']);
                $requests[] = ['query' => $mergedInput, 'timeout' => array_get($childRequestTimeouts, $step['resource'], $generalTimeout)];
            }
        }
        else if (in_array(Resource::BEHAVIOUR_SPLITABLE_BY_FIELD, $step['behaviours'])) {
            // "Splitable by field" AKA "Chunk by field"

            $inputs = self::mapInputs($step, $resourceData);

            // Mapping present and no input? Assume it we got 0 input, and thus no results.
            if ($step['inputMapping'] && $inputs === []) {
                return [];
            }

            $chunks = [];
            foreach ((array)$inputs as $input) {
                $key = 'chunk';
                foreach ($step['parallelChunkByFields'] as $field) {
                    $key .= '::'. array_get($input, $field);
                }
                $chunks[$key][] = $input;
            }

            foreach ($chunks as $chunk) {
                $mergedInput = self::mergeInputArrays($chunk, $step['inputMapping']);
                $requests[] = ['query' => $mergedInput, 'timeout' => array_get($childRequestTimeouts, $step['resource'], $generalTimeout)];
            }
        }
        else {
            $inputs = self::mapMergedInputs($step, $resourceData);

            // Mapping present and no input? Assume it we got 0 input, and thus no results.
            if ($step['inputMapping'] && $inputs === []) {
                return [];
            }

            $requests[] = ['query' => $inputs, 'timeout' => array_get($childRequestTimeouts, $step['resource'], $generalTimeout)];
        }

        return [
            '__parallelRequests' => $requests,
            '__originalStep' => $step,
        ];
    }

    private static function executeParallelCalls(array $step, $resourceData)
    {
        // If we only have one call... don't do it in Parallel
        // Todo: this is a bit ugly, make it a bit neater?
        $count = 0;
        $lastStep = null;
        $minTimeout = self::DEFAULT_REQUEST_TIMEOUT;
        foreach ($resourceData as $resourceName => $resourceItem) {
            if (isset($resourceItem['__parallelRequests'], $resourceItem['__originalStep'])) {
                $count += count($resourceItem['__parallelRequests']);
                $lastStep = $resourceItem['__originalStep'];
                $minTimeout = min($minTimeout, min(array_pluck($resourceItem['__parallelRequests'], 'timeout')));
            }
        }
        if ($count == 1 && $minTimeout === self::DEFAULT_REQUEST_TIMEOUT) {
            $resourceData[$lastStep['outputKey']] = self::callResource2($lastStep, $resourceData);
            return $resourceData;
        }

        // Set up parallel client
        $method = 'GET';
        $client = new Client([
            'defaults' => [
                'headers' => [
                    'Accept-Language' => Request::header('Accept-Language'),
                ]
            ]
        ]);

        // Create requests
//        $client = new Client();
        $firedRequestNames = [];
        $requests = function () use($resourceData, $method, $client, &$firedRequestNames){
            $requests = [];
            foreach ($resourceData as $resourceName => $resourceItem) {
                if (isset($resourceItem['__parallelRequests'])) {
                    foreach ($resourceItem['__parallelRequests'] as $nr => $request) {
                        $url = route('resource.index', ['resource2' =>$resourceName]);
                        $resourceRequestName = $resourceName.'-'.$nr;
                        $firedRequestNames[] = $resourceRequestName;

                         yield $client->getAsync($url, [
                                'connect_timeout' => 2,
                            ] + $request);
//                        cw('Parallel calling '. $method .': '. $requests[$resourceRequestName]->getUrl());
//                        cws('parallel-'. $resourceRequestName, 'Parallel thread `' . $resourceRequestName . '`');
                    }
                }
            }
        };

        // Send all requests
        $successes = [];
        $failures = [];
        $pool = new EachPromise($requests(), [
            'concurrency' => 10,
            'fulfilled' => function (ResponseInterface $response, $index) use (&$successes){
                //Get the resource name from the header
                $resourceName = head($response->getHeader('X-Komparu-Resource'));
                $key = $resourceName . '-' . $index;
                $successes[$key] = \GuzzleHttp\json_decode($response->getBody(), true);
            },
            'rejected' => function ($reason, $index) use(&$failures) {
                $url = (string) $reason->getRequest()->getUri();
                cw('Parallel failure for: ' . $url);
                cw('Reason: ' .$reason->getMessage());
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();

        // Process all request results
        foreach ($firedRequestNames as $resourceRequestName){
            if (isset($successes[$resourceRequestName])){

                if (str_contains($resourceRequestName, '-')) {
                    list($resourceName, ) = explode('-', $resourceRequestName);
                    if (!isset($resourceData[$resourceName]) || isset($resourceData[$resourceName]['__parallelRequests']))
                        $resourceData[$resourceName] = [];
                    $resourceData[$resourceName] = array_merge($resourceData[$resourceName], $successes[$resourceRequestName]);
                }
                else {
                    $resourceData[$resourceRequestName] = $successes[$resourceRequestName];
                }
            }
            else {
                if (str_contains($resourceRequestName, '-')) {
                    list($resourceName, ) = explode('-', $resourceRequestName);
                    if (!isset($resourceData[$resourceName]) || isset($resourceData[$resourceName]['__parallelRequests']))
                        $resourceData[$resourceName] = [];
                }
                else {
                    $resourceData[$resourceRequestName] = [];
                }

            }
        }

        return $resourceData;
    }

    private static function splitByInputField(array $step, array $resourceData)
    {
        if (!isset($resourceData['__input__'][$step['splitField']]) || !is_array($resourceData['__input__'][$step['splitField']]))
            return $resourceData[$step['resource']];

        $values = $resourceData['__input__'][$step['splitField']];

        $results = [];
        foreach ($resourceData[$step['resource']] as $row) {
            foreach ($values as $value) {
                $row[$step['splitField']] = $value;
                $results[] = $row;
            }
        }
        return $results;
    }
}