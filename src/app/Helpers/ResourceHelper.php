<?php
/**
 * User: Roeland Werring
 * Date: 13/09/17
 * Time: 20:44
 *
 */

namespace App\Helpers;

use App\Listeners\Resources2\RestListener;
use App\Models\Field;
use App\Listeners\Resources2\OptionsListener;
use App\Listeners\Resources2\ResourcePlanListener;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Lang;
use Komparu\Input\Contract\Validator;

class ResourceHelper
{
    static $split = false;





    /**
     * Make an html form name based on the original resource field name.
     * All dots will be transformed to a nested html notation with brackets.
     *
     * @param Field $field
     *
     * @return mixed|string
     */
    public static function formName(Field $field)
    {
        $name = $field['name'];

        // No dots in the name means no nesting, just return the name itself.
        if( ! strstr($name, '.')){
            return $name;
        }

        // Create a nested html notation for the dotted field name.
        $name = str_replace('.', '][', $name);
        $name = preg_replace('/\]\[/', '[', $name, 1) . ']';

        return $name;
    }

    /**
     * Get a list of choices from the field rules, if they exists.
     *
     * @param Field $field
     *
     * @return array
     */
    public static function choices(Field $field)
    {
        foreach(explode('|', $field->rules) as $rule){

            if( ! strstr($rule, 'in:')){
                continue;
            }

            // Remove the rule name...
            $match = str_replace('in:', '', $rule);

            // Return if we found choices
            $choices = [];
            foreach(explode(',', $match) as $choice){
                $choices[] = trim($choice);
            }
            return $choices;
        }

        return [];
    }

    /**
     * Based on the rules for each field in the resource, what are the
     * required number of records to cover all resource field possibilities?
     *
     * This can be helpful to the end customer, to show a message that not
     * all situations are covered with the current data.
     *
     * @param Resource $resource
     *
     * @return int
     */
    public static function possibilities(Resource $resource)
    {
        $possibilities = 1;

        foreach($resource->inputs as $field){

            $choices = ResourceHelper::choices($field);

            if($count = count($choices)){
                $possibilities *= $count;
            }
        }

        return $possibilities;
    }

    /**
     * Does this resources has related resources?
     *
     * @param Resource $resource
     * @param ArrayObject $data
     *
     * @return bool
     */
    public static function hasRelatedResourcesByDocumentQuery(Resource $resource, ArrayObject $data)
    {
        // Try to find the primary key
        // No ID, then we can't find related resources
        $id = Arr::get($data->getArrayCopy(), '__id');
        if( ! $id){
            return false;
        }

        list($index, $type) = explode('.', $resource->name);

        /** @var Collection $related */
        $related = static::findByRelatedDocumentQuery($index, $type, $id);

        // No relations
        if( ! $related->count()){
            return false;
        }

        // All rules passed, there is a related resource
        return true;
    }

    /**
     * @param $index
     * @param $type
     * @param $id
     *
     * @return Resource[]
     */
    public static function findByRelatedDocumentQuery($index, $type, $id)
    {
        return CacheHelper::call(function ($index, $type, $id) {

            $related = new Collection();

            foreach(Resource::with(['fields'])->get() as $resource){

                $q = $resource['document_query'];

                // Is this resource related to the document?
                if( ! $q){
                    continue;
                }
                if($q['index'] != $index){
                    continue;
                }
                if($q['type'] != $type){
                    continue;
                }

                $ids = (array) $q['options']['filters']['__id'];
                if( ! in_array($id, $ids)){
                    continue;
                }

                // It is related, add it to collection
                $related->push($resource);
            }

            return $related;

        }, func_get_args());
    }

    /**
     * @param $index
     * @param $type
     * @param $id
     *
     * @return Resource[]
     */
    public static function findByRelatedDocuments($index, $type, $id)
    {
        $related = new Collection();

        foreach(Resource::all() as $resource){
            foreach($resource->documents as $document){

                // Is this resource related to the document?
                if($document['__index'] != $index){
                    continue;
                }
                if($document['__type'] != $type){
                    continue;
                }
                if($document['__id'] != $id){
                    continue;
                }

                // It is related, add it to collection
                $related->push($resource);
                break;
            }
        }

        return $related;
    }


    /**
     * @param Field $field
     *
     * @return array
     */
    public static function prepareRules(Field $field)
    {
        $rules    = array_filter(explode('|', $field->rules), function($val){
            return $val !== "";
        });

        // Inputs are not required if it is a Rest resource and we only want
        // the inputs to filter data.
        if( ! $rules){
            return [];

        }

        // Also trim whitespace for each rule, just to be sure
        $rules = array_map('trim', $rules);

        return $rules;
    }

    /**
     * Validate the input data for the resource.
     *
     * @param Resource $resource
     * @param array $input
     * @param array $action
     *
     * @return array
     */
    public static function validate(Resource $resource, array $input, $action)
    {
        //TODO TAKE THIS THE FUCK AWAY
        return [];
        /** @var Validator $v */
        $v = Cache::tags('resource2', 'validation', 'translations')->rememberForever('Res2-Validation-'. $resource->name .'-'. $action.'-'. substr(md5($resource->updated_at .'-'. Lang::getLocale()), 10), function () use ($resource, $action) {
            $v = App::make(Validator::class);

            $resource->populateFields($action);
            foreach ($resource->fields as $field) {
                $rules = static::prepareRules($field);
                if ($rules !== null) {
                    foreach ($rules as $rule) {

                        // attach the translation
                        $ruleParts = explode(':', $rule);
                        $params = array_slice($ruleParts, 1);
                        $rule .= '(' . Lang::get('errors.validation.' . $ruleParts[0], $params) . ')';

                        $v->setRule($field->name, $rule);
                    }
                }
            }

            return $v;
        });

        $input = array_filter($input, function ($value) {
            return ! is_array($value);
        });

        $messages = [];
        if( ! $v->validate($input)){
            cw('validation failed on resource '.$action.'::'.$resource->name);
            $messages += $v->messages();
        }

        return $messages;
    }


    /**
     * @param Resource $resource
     * @param array $params
     *
     * @return array    tuple with ID and new params
     */
    public static function stripIdFromParams(\App\Models\Resource $resource, array $params)
    {
        $ids = [];

        // Collect the primary keys
        foreach($resource->primaryFields as $field){

            if( ! isset($params[$field->name])){
                continue;
            }

            $ids[] = $params[$field->name];
            unset($params[$field->name]);
        }

        // Return a combined single id
        return [implode('-', $ids), $params];
    }

    public static function callResource2($name, $params = [], $action = RestListener::ACTION_INDEX, $id = null)
    {
        /** @var Resource $resource */
        $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $name, false, true);

        $resource->populateFields($action);
        return self::call($resource, $action, $params, $id);
    }

    public static function patchResource2($name)
    {
        /** @var Resource $resource */
        $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $name, false, true);
        $resource->populateFields(RestListener::ACTION_MAP);

        $callController = App::make('App\Controllers\CallController');
        /** @var JsonResponse $result */
        return $callController->map($resource);
    }

    public static function callResource1($type, $method, $params = [])
    {
        $resource = App::make('resource.' . $type);

        return call_user_func_array([$resource, $method], ['params' => $params, 'path' => $type . '/' . $method]);
    }

    public static function aggregate(Resource $resource, array $input = [])
    {
        require_once app_path() . '/documents.php';

        if ($resource->act_as !== Resource::ACT_AS_REST) {
            throw new InvalidResourceInput($resource, ['Resource should act as rest to perform an aggregate'], $input);
        }

        return CacheHelper::call(function (Resource $resource, $input) {
            list ($index, $type) = explode('.', $resource->name);
            cws('resource_aggregate_process_input_' . $resource->name, 'Prepping resource aggregate input and permissions, `'. $resource->name .'`');

            // Allow modification if the input
            $input = new ArrayObject($input);

            //check permissions and change inputs accordenly
            Event::fire('resource.aggregate.process.permissions', [$resource, $input]);

            Event::fire('resource.aggregate.process.input', [$resource, $input]);
            Event::fire('resource.aggregate.' . $resource->name . '.process.input', [$resource, $input]);
            cwe('resource_aggregate_process_input_' . $resource->name);

            $i = $input->getArrayCopy();
            $aggregate_fields = ['field', 'moment', 'interval', 'from', 'to'];

            $options = [
                'with'       => '',
                'filters' => array_except($i, $aggregate_fields),
                'aggregates' => [
                    'a' => array_only($i, $aggregate_fields) + [
                            'type'    => 'stats',
                            'process' => true
                        ]
                ],
            ];

            cws('resource_aggregate_get_' . $resource->name, 'Processing resource aggregate, `'. $resource->name .'`');
            $data = array_get((array) DocumentHelper::get($index, $type, $options)->aggregations(), 'a', []);
            cwe('resource_aggregate_get_' . $resource->name);

            $data = new ArrayObject($data);

            cws('resource_aggregate_process_after_' . $resource->name, 'Resource aggregate post-processing, `'. $resource->name .'`');
            Event::fire('resource.aggregate.process.after', [$resource, $input, $data]);
            Event::fire('resource.aggregate.' . $resource->name . '.process.after', [$resource, $input, $data]);
            cwe('resource_aggregate_process_after_' . $resource->name);

            return $data->getArrayCopy();
        }, [
            'resource' => $resource,
            'input'    => $input,
            'locale'   => App::getLocale(),
            'action'   => 'aggregate',
        ]);
    }

    /**
     * Starting point for calling a resource. This even will be fired recursively if some
     * field has a reference to another resource.
     * Mapped to input: before process
     * Mapped to output: after process
     *
     * @param Resource $resource
     * @param string $action
     * @param array $input
     * @param null $id
     *
     * @return mixed
     */
    public static function call(Resource $resource, $action = 'index', array $input = [], $id = null)
    {
        // Only add the resource2 models to the document package just in time.
        // It was originally in the start/global.php file, but then it was unnecessary booted for
        // things that has nothing to do with resource2. We now only load it     when calling an
        // actual resource.
//        require_once app_path() . '/documents.php';
        return CacheHelper::call(function (Resource $resource, $action, $input, $id = null) {
            cws('resource_process_call_' . $resource->name, 'Calling resource `'. $resource->name .'`');

            try {
                cws('resource_process_input_' . $resource->name, 'Prepping resource input and permissions, `'. $resource->name .'`');

                // Allow modification if the input
                $input = new ArrayObject($input);
                //check permissions and change inputs accordingly
                Event::fire('resource.process.permissions', [$resource, $input, $action, $id]);

                Event::fire('resource.process.input', [$resource, $input, $action, $id]);
                Event::fire('resource.' . $resource->name . '.process.input', [$resource, $input, $action, $id]);
                cwe('resource_process_input_' . $resource->name);

                cws('resource_process_cache_' . $resource->name, 'Looking for cache `'. $resource->name .'`');
                /**
                 * Check and handle cache. could be pretty with a listener
                 */
                if (!isset($input[OptionsListener::OPTION_SKIP_CACHE]) && ($cachedResult = CacheHelper::processCache($resource, $input, $action)) !== false) {
                    $data = new ArrayObject($cachedResult);
                    // Process the resource with the appropriate action
                    Event::fire('resource.process.after', [$resource, $input, $data, $action, $id]);
                    Event::fire('resource.process.after.' . $action, [$resource, $input, $data, $action, $id]);
                    Event::fire('resource.' . $resource->name . '.process.after', [$resource, $input, $data, $action, $id]);
                    Event::fire('resource.' . $resource->name . '.process.after.' . $action, [$resource, $input, $data, $action, $id]);
                    return $data->getArrayCopy();
                }
                cwe('resource_process_cache_' . $resource->name);

                cws('resource_process_validate_' . $resource->name, 'Validating input `'. $resource->name .'`');
                // Validate the input for the current resource. Possible to set skip_validate
                if (!isset($input[OptionsListener::OPTION_SKIP_VALIDATE])
                    && empty($input[OptionsListener::OPTION_NO_PROPAGATION])
                    && ($messages = static::validate($resource, $input->getArrayCopy(), $action))
                ) {
                    cwe('resource_process_validate_' . $resource->name);
                    throw new InvalidResourceInput($resource, $messages, $input->getArrayCopy(), 'Invalid resource input for `' . $resource->name . '`'.json_encode($messages));
                }
                cwe('resource_process_validate_' . $resource->name);


                // Wrap things in objects, to allow modification in events
                $data = new ArrayObject();

                // Before we do any call, allow altering the process
                /**
                 * We do not use the before at all cause shit will be on nuclear fire
                 */
                Event::fire('resource.process.before', [$resource, $input, $data, $action, $id]);


                // Process the resource with the appropriate action
                // If something is returned, then we stop other events from being called.
                if (!empty($input[OptionsListener::OPTION_NO_PROPAGATION])) {

                    if (!empty($input[OptionsListener::OPTION_USE_PLAN])) {
                        $result = ResourcePlanListener::process($resource, $input, $data, $action, $id);
                        $data->exchangeArray($result);
                    }
                    else {
                        Event::until('resource.process', [$resource, $input, $data, $action, $id]);

                        $isCollection = ($action == 'index' && ($resource->act_as == 'rest' || $resource->act_as == 'service_rest')) || $resource->act_as == 'collection';
                        if ($isCollection) {
                            Event::fire('resource.collection.after', [$resource, $input, $data, new ArrayObject(), $action, $id]);
                            Event::fire('resource.' . $resource->name . '.collection.after', [$resource, $input, $data, new ArrayObject(), $action, $id]);
                        } else {
                            Event::fire('resource.row.after', [$resource, $input, $data, $action, $id]);
                            Event::fire('resource.' . $resource->name . '.row.after', [$resource, $input, $data, $action, $id]);
                        }
                    }
                }
                else {
                    // Using this event will trigger an infinite loop, probably fixable, but just inserted and IF here..
                    // Event::until('resource.process.propagated', [$resource, $input, $data, $action, $id]);
                    //HACK
                    $input[OptionsListener::OPTION_USE_PLAN] = true;
                    if (!empty($input[OptionsListener::OPTION_USE_PLAN])){
                        $result = ResourcePlanListener::process($resource, $input, $data, $action, $id);
                    }
                    else{
                        $result = ResourceRecursionListener::process($resource, $input, $data, $action, $id);
                    }


                    $data->exchangeArray($result);
                }

                cwe('resource_process_call_' . $resource->name);

                /*
                 * We store the result in cache before we launch the after processes.
                 *
                 * This way we can still alter cached results!
                 */
                CacheHelper::storeCache($resource, $input, $data->getArrayCopy());

                cws('resource_process_after_' . $resource->name, 'Applying process.after events `'. $resource->name .'`');
                // Process the resource with the appropriate action
                Event::fire('resource.process.after', [$resource, $input, $data, $action, $id]);
                Event::fire('resource.process.after.' . $action, [$resource, $input, $data, $action, $id]);
                Event::fire('resource.' . $resource->name . '.process.after', [$resource, $input, $data, $action, $id]);
                Event::fire('resource.' . $resource->name . '.process.after.' . $action, [$resource, $input, $data, $action, $id]);
                cwe('resource_process_after_' . $resource->name, 'Applying process.after events `'. $resource->name .'`: '. count($data) .' results');

                return $data->getArrayCopy();
            }
            finally
            {
                cwe('resource_process_call_' . $resource->name);
            }
        }, func_get_args(), [
            'resource' => $resource->id,
            'action'   => $action,
            'input'    => $input,
            'id'       => $id,

            //TODO FIX
            'locale'   => Lang::getLocale(),
        ]);

    }

    /**
     * @param Resource $resource
     * @param array $input
     *
     * @return array
     */
    public static function getVisible(\App\Models\Resource $resource, Array $input)
    {
        // Return empty array if resource doesn't have the behaviour
        if( ! $resource->hasBehaviour(Resource::BEHAVIOUR_VIEWABLE)){
            return [];
        }

        // Get the visible fields from the input
        $visible = Arr::get($input, OptionsListener::OPTION_VISIBLE);

        // No fields, just return empty array
        if( ! $visible){
            return [];
        }

        // Fields are separated by a comma.
        if(is_string($input[OptionsListener::OPTION_VISIBLE])){
            return explode(',', $input[OptionsListener::OPTION_VISIBLE]);
        }
        return $input[OptionsListener::OPTION_VISIBLE];
    }

    /**
     * @param Field $field
     * @param Resource $resource
     * @param array $input
     *
     * @return bool
     */
    public static function isVisible(Field $field, \App\Models\Resource $resource, Array $input)
    {
        $visible = static::getVisible($resource, $input);

        if( ! $visible){
            return true;
        }

        return in_array($field->name, $visible);
    }

    /**
     * @param Resource $resource
     * @param string $fieldName
     *
     * @return bool
     */
    public static function isDocumentField(\App\Models\Resource $resource, $fieldName)
    {
        $fields = $resource->fields->keyBy('name');

        /** @var Field $field */
        $field = Arr::get($fields, $fieldName);

        if( ! $field){
            return false;
        }

        // This is the way to check if the field is a Document field
        return $field->hasFilter(Field::FILTER_PATCH);
    }

    /**
     * @return array
     */
    public static function getReservedParamNames()
    {
        return [
            OptionsListener::OPTION_ORDER,
            OptionsListener::OPTION_LIMIT,
            OptionsListener::OPTION_LIMIT_DOUBLE_UNDERSCORE,
            OptionsListener::OPTION_VISIBLE,
            OptionsListener::OPTION_DIRECTION,
            OptionsListener::OPTION_OFFSET,
            OptionsListener::OPTION_OFFSET_DOUBLE_UNDERSCORE,
            OptionsListener::OPTION_SPLIT,
            OptionsListener::OPTION_IGNORE_SETTINGS,
            OptionsListener::OPTION_SKIP_REST,
            OptionsListener::OPTION_SUB_FILTER,
            OptionsListener::OPTION_PERMISSIONS_FILTER,
            OptionsListener::OPTION_FIELD_DIFF,
            OptionsListener::OPTION_USE_PLAN,
            OptionsListener::OPTION_PERMISSIONS_APPLIED,
            'conditions',
            'filters'
        ];
    }

    /**
     * Helper to check if the cols are visible or hidden
     *
     * @param ArrayObject $input
     * @param array $cols
     *
     * @return bool
     */
    public static function checkColsVisible(ArrayObject $input, $cols = [])
    {
        if( ! isset($input[OptionsListener::OPTION_VISIBLE])){
            return true;
        }
        $visibleArr = is_array($input[OptionsListener::OPTION_VISIBLE]) ? $input[OptionsListener::OPTION_VISIBLE] : explode(',', $input[OptionsListener::OPTION_VISIBLE]);
        foreach($cols as $col){
            if( ! (in_array($col, $visibleArr))){
                return false;
            }
        }
        return true;
    }
}