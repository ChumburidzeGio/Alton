<?php

namespace App\Listeners\Resources2;

use App;
use App\Helpers\ResourceHelper;
use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Komparu\Document\ArrayHelper;
use Komparu\Document\Contract\OptionsFactory;
use Komparu\Value\Type;
use Komparu\Value\ValueInterface;

class OptionsListener
{
    const OPTION_LIMIT = '_limit';
    const OPTION_LIMIT_DOUBLE_UNDERSCORE = '__limit';
    const OPTION_OFFSET = '_offset';
    const OPTION_OFFSET_DOUBLE_UNDERSCORE = '__offset';
    const OPTION_ORDER = '_order';
    const OPTION_DIRECTION = '_direction';
    const OPTION_VISIBLE = '_visible';
    const OPTION_SPLIT = '_split';
    const OPTION_USE_PLAN = '_use_plan';
    const OPTION_LANG = '_lang';
    const OPTION_IGNORE_SETTINGS = '_ignore_settings';
    const OPTION_SUB_FILTER = '_sub_filter';
    const OPTION_PERMISSIONS_FILTER = '_permissions_filter';
    const OPTION_PERMISSIONS_APPLIED = '_permissions_applied';
    const OPTION_FIELD_DIFF = '_field_diff';
    const OPTION_DELETED_DATA = '_deleted_data';
    const OPTION_FORCE_LIMIT = '_force_limit';
    const OPTION_RESOURCE_TIMEOUT = '_resource_timeout';
    const OPTION_TIMESTAMP = '_timestamp';

    /**
     * In case you want to post, but not post to the database
     */
    const OPTION_SKIP_REST = '_ignore_settings';

    /**
     * Skip the validation rules
     */
    const OPTION_SKIP_VALIDATE = '_skip_validate';
    /**
     * Completely bypass the service request
     */
    const OPTION_BYPASS = '_bypass';
    const OPTION_NO_PROPAGATION = '_no_propagation';
    const OPTION_SKIP_CACHE = '_skip_cache';
    const OPTION_NO_TRANSLATION = '_no_translation';


    const OPTION_DIRECTION_ASC = 'asc';
    const OPTION_DIRECTION_DESC = 'desc';

    const ALL_OPTIONS = [
        self::OPTION_LIMIT,
        self::OPTION_LIMIT_DOUBLE_UNDERSCORE,
        self::OPTION_OFFSET,
        self::OPTION_OFFSET_DOUBLE_UNDERSCORE,
        self::OPTION_ORDER,
        self::OPTION_DIRECTION,
        self::OPTION_VISIBLE,
        self::OPTION_SPLIT,
        self::OPTION_USE_PLAN,
        self::OPTION_LANG,
        self::OPTION_IGNORE_SETTINGS,
        self::OPTION_SUB_FILTER,
        self::OPTION_PERMISSIONS_FILTER,
        self::OPTION_PERMISSIONS_APPLIED,
        self::OPTION_FIELD_DIFF,
        self::OPTION_DELETED_DATA,
        self::OPTION_FORCE_LIMIT,
        self::OPTION_RESOURCE_TIMEOUT,
        self::OPTION_SKIP_REST,
        self::OPTION_SKIP_VALIDATE,
        self::OPTION_BYPASS,
        self::OPTION_NO_PROPAGATION,
        self::OPTION_SKIP_CACHE,
        self::OPTION_NO_TRANSLATION,
        self::OPTION_DIRECTION_ASC,
        self::OPTION_DIRECTION_DESC
    ];

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process.input', [$this, 'setPlan']);
        $events->listen('resource.process.input', [$this, 'setLanguage']);
        $events->listen('resource.process.input', [$this, 'setInputDefault']);
        $events->listen('resource.process.before', [$this, 'removeOrder']);
        $events->listen('resource.process.before', [$this, 'removeLimit']);
        $events->listen('resource.collection.after', [$this, 'translate']);
        $events->listen('resource.collection.after', [$this, 'order']);
        $events->listen('resource.collection.after', [$this, 'addLimit']);
        $events->listen('resource.row.after', [$this, 'visible']);
    }


    /**
     * Enable plan for resources with this behaviour automatically
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function setPlan(Resource $resource, ArrayObject $input)
    {
        // Have we specified the _use_plan manually? use that.
        if (isset($input[OptionsListener::OPTION_USE_PLAN])) {
            return;
        }

        if ($resource->hasBehaviour(Resource::BEHAVIOUR_USE_PLAN)) {
            $input->offsetSet(OptionsListener::OPTION_USE_PLAN, true);
        }
    }


    /**
     * Set the input defaults, if in put not set
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function setInputDefault(Resource $resource, ArrayObject $input)
    {
        if (!empty($input[OptionsListener::OPTION_USE_PLAN])) {
            return;
        }

        $inputArr = $input->getArrayCopy();

        $resourceFields = $resource->fields;
        foreach ($resourceFields as $field) {
            if (!$field->input || $field->input_default === null) {
                continue;
            }

            if (array_get($inputArr, $field->name) !== null) {
                continue;
            }

            if ($field->type === Type::DATETIME) {
                $date = strtotime($field->input_default);
                if ($date !== false) {
                    $field->input_default = (new \DateTime("@$date"))->format('Y-m-d H:i:s');
                }
            }

            //use place holders
            if (preg_match('/{{(.+)}}/', $field->input_default, $matches) && isset($matches[1]) && $input->offsetExists($matches[1])) {
                array_set($inputArr, $field->name, $input->offsetGet($matches[1]));
                continue;
            }
            array_set($inputArr, $field->name, $field->input_default);

        }
        $input->exchangeArray($inputArr);
    }

    /**
     * Set the locale to _lang, if present.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     */
    public static function setLanguage(Resource $resource, ArrayObject $input)
    {
        if (empty($input[OptionsListener::OPTION_LANG])) {
            return;
        }

        App::setLocale($input[OptionsListener::OPTION_LANG]);
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param null $id
     */
    public static function removeLimit(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        // Don't need the event if we are not gonna call the external source.
        // Return early if we skip propagation.
        //        if(isset($input['_no_propagation'])){
        //            return;
        //        }

        if (!empty($input[self::OPTION_FORCE_LIMIT])) {
            return;
        }

        // Check if the resource can accept limit params

        if ((!$resource->hasBehaviour(Resource::BEHAVIOUR_LIMITABLE)) && (!$resource->hasBehaviour(Resource::BEHAVIOUR_UNLIMITABLE))) {
            return;
        }

        // We only need to remove the limit of there are child resources that depend on this parent resource.

        //$info = ResourceHelper::info($resource, $input->getArrayCopy(), $action);
        //if(!$info['after']) return;
        //FIX: disabled this, just use limitable

        if ($resource->hasBehaviour(Resource::BEHAVIOUR_LIMITABLE)) {
            // Remember the old limit, because we need to put it back later
            // We use the default limit from the Document package here.
            $input['__limit'] = Arr::get($input, static::OPTION_LIMIT, OptionsFactory::DEFAULT_LIMIT);
        }

        //also offset needs to be add after results!
        $input['__offset'] = Arr::get($input, static::OPTION_OFFSET, 0);

        //set offset to 0, we want to see all
        $input[static::OPTION_OFFSET] = 0;

        // Set a very large limit for the child resource.
        // We have to set something here, otherwise a default fallback of 10 is used,
        // and we explicitly don't want that to happen.
        $input[static::OPTION_LIMIT] = ValueInterface::INFINITE;

    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param null $id
     */
    public static function removeOrder(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        // Don't need the event if we are not gonna call the external source.
        // Return early if we skip propagation.
        //        if(isset($input['_no_propagation'])){
        //            return;
        //        }

        if (!empty($input[self::OPTION_FORCE_LIMIT])) {
            return;
        }

        // Check if the resource can sort and accept order params
        if (!$resource->hasBehaviour(Resource::BEHAVIOUR_SORTABLE)) {
            return;
        }
        if (!isset($input[static::OPTION_ORDER])) {
            return;
        }

        // Get the field to order by
        $order = Arr::get($input, static::OPTION_ORDER);


        // We only need to do something if this order field is not a generic Document package field.
        // Document fields can always be sorted, so we don't need this event.

        //RW: took this out cause sometimes we want to force additional sorting afterwards.
        //if(ResourceHelper::isDocumentField($resource, $order)) return;


        // Remember the old order, because we need to put it back later
        $input['__order'] = $order;

        //unset the order
        unset($input[static::OPTION_ORDER]);

    }

    /**
     * Handle the 'translatable' field option.
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param null $id
     */
    public static function translate(Resource $resource, ArrayObject $input, ArrayObject $data, ArrayObject $resolved, $action, $id = null)
    {
        if ($resource->name != 'models.region') {
            //   dd($data->getArrayCopy());
        }

        if (isset($input[self::OPTION_NO_TRANSLATION])) {
            return;
        }

        // Only apply for output
        if (!in_array($action, ['index', 'show'])) {
            return;
        }

        $translateFields = [];
        foreach ($resource->fields as $field) {
            if (in_array(Field::FILTER_TRANSLATABLE, $field->filters)) {
                $translateFields[] = $field;
            }
        }

        if (!$translateFields) {
            return;
        }

        foreach ($data as $key => $item) {
            foreach ($translateFields as $field) {
                $data[$key][$field->name] = App\Helpers\TranslationHelper::getFieldTranslation($data[$key][$field->name], $resource->name . '.' . $field->name);
            }
        }
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param null $id
     */
    public static function order(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {

        // Don't need the event if we are not gonna call the external source.
        // Return early if we skip propagation.
        //        if(isset($input['_no_propagation'])){
        //            return;
        //        }

        if (!empty($input[self::OPTION_FORCE_LIMIT])) {
            return;
        }

        // Check if the resource can sort an accept limit params
        if (!$resource->hasBehaviour(Resource::BEHAVIOUR_SORTABLE)) {
            return;
        }

        Event::fire('resource.' . $resource->name . '.order.before', [$resource, $input, $data, $action, $id]);

        // Get the field that we need to sort the collection by.
        $order = Arr::get($input->getArrayCopy(), '__order');

        // Check if we have a sort param in the input
        if (!$order) {
            return;
        }

        // Get the direction to sort
        $descending = Arr::get($input->getArrayCopy(), static::OPTION_DIRECTION) == static::OPTION_DIRECTION_DESC ? true : false;

        // Get the collection sorted by the right field

        $sorted = Collection::make($data->getArrayCopy())->sortBy($order, null, $descending)->values()->toArray();
        $data->exchangeArray($sorted);
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param null $id
     */
    public static function addLimit(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        // Don't need the event if we are not gonna call the external source.
        // Return early if we skip propagation.
        //        if(isset($input['_no_propagation'])){
        //            return;
        //        }
        Event::fire('resource.' . $resource->name . '.limit.before', [$resource, $input, $data, $action, $id]);
        $total = count($data);
        if ($resource->hasBehaviour(Resource::BEHAVIOUR_UNLIMITABLE)) {
            if (!(strpos(php_sapi_name(), 'cli') !== false)) {
                header("X-Total-Count: " . $total);
            }

            return;
        }


        // Check if the resource can sort an accept limit params
        if (!$resource->hasBehaviour(Resource::BEHAVIOUR_LIMITABLE)) {
            return;
        }

        // Check if we have the original limit from previous event hook
        if (!isset($input['__limit'])) {
            return;
        }


        // Do we need to skip some records, defaults to 0
        $offset = Arr::get($input->getArrayCopy(), '__offset', 0);

        if (!(defined('TOTAL_HEADER_SENT') and TOTAL_HEADER_SENT)) { // Add the total count header for pagination purposes
            if (!(strpos(php_sapi_name(), 'cli') !== false)) {
                header("X-Total-Count: " . $total);

                // set content range
                $range = sprintf('Content-Range: %s %d-%d/%d', $resource->name, $offset, $input['__limit'], $total);
                header($range);
            }
            define('TOTAL_HEADER_SENT', true);
        }

//        if(isset($input['_no_propagation'])){
//            return;
//        }

        // If this is the case, then we need to limit the results again, based on the original input
        $limited = Collection::make($data->getArrayCopy())->slice($offset, $input['__limit'])->toArray();
        $data->exchangeArray($limited);
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $data
     * @param $action
     * @param null $id
     */
    public static function visible(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        // Check if the resource can sort an accept limit params
        if (!$resource->hasBehaviour(Resource::BEHAVIOUR_VIEWABLE)) {
            return;
        }

        // Get the visible fields from the input.
        $visible = ResourceHelper::getVisible($resource, $input->getArrayCopy());

        // No visible fields in the input? Then there is nothing to do.
        if (!$visible) {
            return;
        }

        // Narrow down the current data and only use the data from the fields
        // that can be viewed. We need to assume there could be nested values
        // here, so deep merge the values in the new dataset.
        $viewable = [];
        foreach ($visible as $key) {
            ArrayHelper::set($viewable, $key, Arr::get($data, $key));
        }

        $data->exchangeArray($viewable);
    }
}