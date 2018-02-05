<?php

namespace App\Listeners\Resources2;

use App\Exception\PrettyServiceError;
use App\Helpers\DocumentHelper;
use App\Helpers\QueryHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Komparu\Document\Contract\Options;
use Komparu\Document\Contract\Result;
use Komparu\Document\Exception\DocumentNotFound;
use Komparu\Value\ValueInterface;

class RestListener
{
    const RESERVED = ['created_at'];

    const ACTION_INDEX = 'index';
    const ACTION_STORE = 'store';
    const ACTION_SHOW = 'show';
    const ACTION_UPDATE = 'update';
    const ACTION_DESTROY = 'destroy';
    const ACTION_TRUNCATE = 'truncate';
    const ACTION_BULK = 'bulk';
    const ACTION_MAP = 'map';

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process.input', [$this, 'setDefaultOrder']);
        $events->listen('resource.process', [$this, 'filterEmptyInput']);
        //$events->listen('resource.process', [$this, 'callConditionsParallel']);
        $events->listen('resource.process', [$this, 'process']);
    }

    /**
     * Set defaultorder to first field with FILTER_DEFAULT_ORDER if present
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public function setDefaultOrder(Resource $resource, ArrayObject $input)
    {
        if (isset($input[OptionsListener::OPTION_ORDER])) {
            return;
        }

        // make sure we use the populated Fields
        $resourceFields = $resource->fields;

        foreach($resourceFields as $field) {
            if($field->hasFilter(Field::FILTER_DEFAULT_ORDER) ) {
                $input->offsetSet('_' . ResourceInterface::ORDER, $field->name);
                return;
            }
        }
    }


    /**
     * Filter empty input params
     */
    public static function filterEmptyInput(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        $filtered = array_filter($input->getArrayCopy(), function($value) {
           return $value !== '';
        });

        $input->exchangeArray($filtered);
    }

    /**
     * Handle a REST resource. This resource is built with the resource builder
     * and has multiple routes (actions).
     */
    public static function process(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null, $behaviourOverride = false)
    {
        // Only continue of this is an actual REST resource
        if ($resource->act_as != Resource::ACT_AS_REST && !$behaviourOverride) return;


        list($index, $type) = explode('.', $resource->name);

        // Remove null value from input, otherwise filters will filter wrong data
        $input = array_filter($input->getArrayCopy(), function ($value) {
            return $value !== null;
        });

        // These are reserved option params with _
        $reserved = ResourceHelper::getReservedParamNames();

        // Get the params from the input
        $params = Arr::except($input, $reserved);

        // Transform the input to filters for use in the document package
        $filters = static::buildFilters($resource, $params);

        // Apply permission filters, if present
        $permissionFilters = isset($input[OptionsListener::OPTION_PERMISSIONS_FILTER]) ? static::buildFilters($resource, (array)$input[OptionsListener::OPTION_PERMISSIONS_FILTER]) : [];
        $filters = array_merge($filters, $permissionFilters);

        // Transform the options for use in the document package
        $options = static::buildOptions($resource, Arr::only($input, $reserved));

        // Check for each field how to handle the input params
        $conditions = static::buildConditions($resource, $input, Field::FILTER_CONDITION);

//        $conditions2 = static::buildConditions($resource, $input, Field::FILTER_CONDITION2);

        // Put the collected conditions in the options
        if($conditions) {
            $options['conditions']  = $conditions;
//            $options['conditions2'] = $conditions2;
        }

        switch ($action) {

            case self::ACTION_INDEX:
//                $response = DocumentHelper::get($index, $type, ['filters' => $filters] + $options);
//                dd($response->documents()->toArray());
                $response = QueryHelper::index($index, $type, $resource, ['filters' => $filters] + $options);
//                dd(head($response));
                $data->exchangeArray($response);
                // Add the total count header for pagination purposes

                if (!(defined('TOTAL_HEADER_SENT') and TOTAL_HEADER_SENT)) {
                    if (!(strpos(php_sapi_name(), 'cli') !== false)) {
                        header("X-Total-Count: " . count($response));

                        // Add the content range header
                        $offset = array_get($input, '__offset', array_get($options, 'offset', 0));
                        $limit = array_get($input, '__limit', array_get($options, 'limit', ValueInterface::INFINITE));

                        $range = sprintf('Content-Range: %s %d-%d/%d', $resource->name, $offset, $limit, count($response));
                        header($range);
                    }
                    define('TOTAL_HEADER_SENT', true);
                }
                break;

            case self::ACTION_STORE:
                //check for unique keys
                $fields = $resource->getUniqueFields();
                if (count($fields)){
                    $find= [];
                    foreach($fields as $field){
                        if (!isset($input[$field->name])) {
                            //possibly throw error??
                            continue;
                        }
                        $find[$field->name] = $input[$field->name];
                    }
                    $result = QueryHelper::index($index, $type, $resource, ['filters' => $find]);
                    if (count($result->toArray())) {
                        $str = 'There is already an entry with '.implode(',',array_keys($find)).' \''.implode(', ',array_values($find)).'\'';
                        throw new PrettyServiceError($resource, $input, $str);

                    }
                }
                $insertData = array_except($params, array_keys($conditions));
//                $insertData = array_except($insertData, array_keys($conditions2));

                cw($insertData);

                unset($insertData['_no_propagation']);
                $response = QueryHelper::store($index, $type, $insertData, $resource, $options);
                $item = $response->toArray();

                //TODO: Investigate why this needed to be done by Daan like this. Since no shitty travel anymore
                //we probably do not need this
                try {
                    self::checkPermissionsFilters($index, $type, $item['__id'], $permissionFilters);
                }
                catch (DocumentNotFound $e) {
                    // Created an object that we're not allowed to access? Bad! Abort!
                    QueryHelper::destroy($index, $type, $item['__id'], $resource);
                    throw new \Exception('Cannot create that item: not allowed.');
                }

                $response = QueryHelper::show($index, $type, $item['__id'], $resource, ['filters' => $permissionFilters]);
                $item = $response->toArray();
                $data->exchangeArray($item);

                break;

            case self::ACTION_SHOW:
                self::checkPermissionsFilters($index, $type, $id, $permissionFilters);

                $response = QueryHelper::show($index, $type, $id, $resource, ['filters' => $filters] + $options);
                if($response){
                    $item = $response->toArray();
                    $data->exchangeArray($item);
                }
                break;

            case self::ACTION_UPDATE:
                self::checkPermissionsFilters($index, $type, $id, $permissionFilters);

                $updateData = array_except($params, array_keys($conditions));
//                $updateData = array_except($updateData, array_keys($conditions2));
                $updateData = array_except($updateData, OptionsListener::ALL_OPTIONS);

                unset($updateData['_no_propagation']);
                $response = QueryHelper::update($index, $type, $id, $updateData, $resource, $options);
                $data->exchangeArray($response->toArray());
                break;

            case self::ACTION_DESTROY:
                self::checkPermissionsFilters($index, $type, $id, $permissionFilters);

                $response = QueryHelper::destroy($index, $type, $id, $resource, $options);
                $item = $response->toArray();
                $data->exchangeArray($item);
                break;

            case self::ACTION_TRUNCATE:
                $response = QueryHelper::truncate($index, $type, $resource, $options);
                $item     = $response->toArray();
                $data->exchangeArray($item);
                break;

            case self::ACTION_BULK:
                //TODO: Implement when necessary
                dd('Not implemented!');
                break;
            case self::ACTION_MAP:
                $response = QueryHelper::createOrUpdateTable($index, $type, $resource);
                $data->exchangeArray($response);
                break;

            default:
                throw new \Exception('Unknown REST action: `'. $action .'`');
        }

    }

    /**
     * @param Resource $resource
     * @param array $rawOptions
     * @return array
     */
    protected static function buildOptions(Resource $resource, Array $rawOptions)
    {
        /** @var Field[] $fields */
        $fields = collect($resource->fields)->keyBy('name');

        $options = [];
        foreach($rawOptions as $key => $value) {

            //we use __  prefix to temporary store stuff, should not be processed!
            if (str_contains($key,'__')) {
                continue;
            }

            // Remove the prefix if it exists
            $keyWithoutPrefix = str_replace('_', '', $key);
            $options[$keyWithoutPrefix] = $value;
        }

        return $options;
    }

    /**
     * @param Resource $resource
     * @param array $input
     * @return array
     */
    public static function buildFilters(Resource $resource, Array $input)
    {
        /** @var Field[] $fields */
        $fields = collect($resource->fields)->keyBy('name');
        $filters = [];

        $keys = array_keys($input);

        for ($i = 0; $i < count($input); $i++) {
            $key = $keys[$i];
            $value = $input[$keys[$i]];

            if (preg_match('/^\$/', $key)) {
                $filters[$key] = array_map(function ($or) use ($resource) {
                    return self::buildFilters($resource, $or);
                }, $value);
            }

            if (is_array($value) and \Komparu\Utility\ArrayHelper::isAssoc($value)) {
                $dots = [];
                foreach ($value as $k => $v) {
                    $dots[$key . '.' . $k] = $v;
                }
                $input = array_merge($input, $dots);
                $keys  = array_merge($keys, array_keys($dots));
            }

            if (!in_array($key, self::RESERVED)
                and (!isset($fields[$key]) or !$fields[$key]->hasFilter(Field::FILTER_PATCH))
            ) {
                continue;
            }

            $filters[$key] = $value;
        }

        return $filters;
    }

    /**
     * @param Resource $resource
     * @param array $input
     * @param string $filter
     *
     * @return array
     */
    protected static function buildConditions(Resource $resource, Array $input, $filter = Field::FILTER_CONDITION)
    {
        $conditions = [];
        $orders = [];

        foreach($resource->fields as $field) {

            // If the field is a condition, then we will use the input value as condition
            if($field->hasFilter($filter) && $value = Arr::get($input, $field->name)) {
                $conditions[$field->name] = $value;
                $orders[$field->name] = $field->order ?? 0;
            }
        }

        return compact('conditions', 'orders');
    }

    protected static function checkPermissionsFilters($index, $type, $id, $permissionFilters)
    {
        if (!$permissionFilters)
            return;

        $result = QueryHelper::get($index, $type, [
            'filters' => array_merge(
                $permissionFilters,
                [ResourceInterface::__ID => $id, OptionsListener::OPTION_VISIBLE => [ResourceInterface::__ID]]
            ),
        ]);

        if (count($result->toArray()) === 0)
            throw new DocumentNotFound();
    }
}