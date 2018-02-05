<?php

namespace App\Listeners\Resources2;

use App\Models\Field;
use App\Models\Resource;
use App\Models\User;
use ArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Komparu\Value\ValueInterface;

class EloquentRestListener
{

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process', [$this, 'process']);
    }

    /**
     * Handle a Wrapped REST resource. This resource is an eloquent model (usually) that is exposed as a REST endpoint
     * and has multiple routes (actions).
     */
    public static function process(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        // Only continue of this is an actual Wrapped REST resource
        if($resource->act_as != Resource::ACT_AS_ELOQUENT_REST){
            return;
        }

        list($modelName, $type) = explode('.', $resource->name);

        $model = null;
        if($resource->eloquent !== null){
            //We have an eloquent model that has a different name than the resource name part
            $model = app($resource->eloquent);
        }else{
            $model = app('App\\Models\\' . ucfirst($modelName));
        }

        if( ! $model){
            throw new ModelNotFoundException('Model not found');
        }

        if (!(strpos(php_sapi_name(), 'cli') !== false)) {
            $application = app('application');

            $user = User::find($application->user_id);
        }

        // Remove null value from input, otherwise filters will filter wrong data
        $input = array_filter($input->getArrayCopy(), function ($value) {
            return $value !== null;
        });

        //Currently everybody has access to everything if a login exists
        //TODO: Add Role Based Access Control here!

        if( ! is_null($resource['cache_lifetime'])){
            if (!(strpos(php_sapi_name(), 'cli') !== false))
                header("X-Komparu-Cache-Control: " . $resource['cache_lifetime']);
        }

        //$reserved = ResourceHelper::getReservedParamNames();;

        if(isset($input['__id']) && $action === 'index'){
            $action = 'show';
            $id     = $input['__id'];
        }
        $offset     = isset($input[OptionsListener::OPTION_OFFSET]) ? $input[OptionsListener::OPTION_OFFSET] : 0;
        $limit      = isset($input[OptionsListener::OPTION_LIMIT]) ? $input[OptionsListener::OPTION_LIMIT] : ValueInterface::INFINITE;
        $visible    = static::buildVisible($model, $resource, $input);
        $order      = isset($input[OptionsListener::OPTION_ORDER]) ? $input[OptionsListener::OPTION_ORDER] : 'id';
        $direction = (isset($input[OptionsListener::OPTION_DIRECTION]) ? $input[OptionsListener::OPTION_DIRECTION] : OptionsListener::OPTION_DIRECTION_ASC);
        $descending = $direction === OptionsListener::OPTION_DIRECTION_DESC ? true : false;

        $filters = static::buildFilters($model, $input);

        switch($action){

            case 'index':
                $query = $model->query()->take(ValueInterface::INFINITE);

                if($filters){
                    $query = static::addFilters($query, $filters);
                }

                $query = self::addPermissionsFilter($query, $input);

                $response      = $query->get($visible);
                $responseArray = $response->toArray();

                // If this is the case, then we need to limit the results again, based on the original input
                $limited = Collection::make($responseArray)->sortBy($order, null, $descending)->slice($offset, $limit)->toArray();
                $data->exchangeArray($limited);

                if (!(defined('TOTAL_HEADER_SENT') and TOTAL_HEADER_SENT)) { // Add the total count header for pagination purposes
                    $total = count($responseArray);
                    if (!(strpos(php_sapi_name(), 'cli') !== false)) {
                        header("X-Total-Count: " . $total);

                        $range = sprintf('Content-Range: %s %d-%d/%d', $resource->name, $offset, $limit, $total);
                        header($range);
                    }
                    define('TOTAL_HEADER_SENT', true);
                }
                break;

            case 'store':
                //TODO: Implement Store Method
                $item                     = $model->create($input);

                if (!self::addPermissionsFilter($model->query(), $input)->find($item->id)) {
                    // We created an item we're not allowed to see? Baaad - delete it.
//                    $item->destroy();
//                    throw new \Exception('Cannot create that item: not allowed.');
                }

                $data->exchangeArray($item->toArray());
                break;

            case 'show':
                $item = self::addPermissionsFilter($model->query(), $input)->findOrFail($id, $visible);
                $data->exchangeArray($item->toArray());
                break;

            case 'update':
                unset($input['_visible']);
                unset($input['id']);
                $item = self::addPermissionsFilter($model->query(), $input)->findOrFail($id, $visible);
                $item->update($input);
                $data->exchangeArray($item->toArray());
                break;

            case 'destroy':
                $item = self::addPermissionsFilter($model->query(), $input)->findOrFail($id);
                $model->destroy($item->id);
                $data->exchangeArray(['success' => true]);
                break;
        }

    }

    public static function buildFilters(Model $model, Array $input)
    {
        /** @var Field[] $fields */
        $filters = [];
        if(method_exists($model, 'getFilterFields')){
            $fields = array_flip($model->getFilterFields());
            $keys   = array_keys($input);

            for($i = 0; $i < count($input); $i ++){
                $key   = $keys[$i];
                $value = $input[$keys[$i]];

                if(preg_match('/^\$/', $key)){
                    $filters[$key] = array_map(function ($or) use ($model) {
                        return self::buildFilters($model, $or);
                    }, $value);
                }

                if(is_array($value) and \Komparu\Utility\ArrayHelper::isAssoc($value)){
                    $dots = [];
                    foreach($value as $k => $v){
                        $dots[$key . '.' . $k] = $v;
                    }
                    $input = array_merge($input, $dots);
                    $keys  = array_merge($keys, array_keys($dots));
                }

                if( ! isset($fields[$key])){
                    continue;
                }
                $filters[$key] = str_replace('*', '%', $value);
            }
        }
        return $filters;
    }


    private static function addFilters($query, $filters)
    {
        foreach($filters as $fieldName => $filter){
            if(is_string($filter) && strpos($filter, '%') !== false){
                $query  = $query->where($fieldName, 'like', $filter);
            }
            else if (is_scalar($filter)) {
                $query = $query->where($fieldName, '=', $filter);
            }
            else if (is_array($filter)) {
                $query = $query->whereIn($fieldName, $filter);
            }
        }
        return $query;
    }

    private static function addComplexFilter($query, $complexFilter)
    {
        foreach ($complexFilter as $field => $filterMap) {
            foreach ($filterMap as $method => $arguments) {
                $query->{$method}($field, function ($query) use ($field, $arguments) {
                    foreach ($arguments as $argumentMethod => $argumentValues) {
                        if (is_array($argumentValues)) {
                            $query->{$argumentMethod}(...$argumentValues);
                        } else {
                            $query->{$argumentMethod}($argumentValues);
                        }
                    }
                });
            }
        }
        return $query;
    }

    protected static function addPermissionsFilter(Builder $query, $input)
    {
        if (!isset($input[OptionsListener::OPTION_PERMISSIONS_FILTER]))
            return $query;

        $query = self::addFilters($query, self::buildFilters($query->getModel(), $input[OptionsListener::OPTION_PERMISSIONS_FILTER]));

        if (isset($input[OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_SUB_FILTER])){
            $query = static::addComplexFilter($query, $input[OptionsListener::OPTION_PERMISSIONS_FILTER][OptionsListener::OPTION_SUB_FILTER]);
        }

        return $query;
    }

    public static function buildVisible(Model $model, Resource $resource, Array $input)
    {
        $inputVisible = array_get($input, OptionsListener::OPTION_VISIBLE, null);
        if (is_string($inputVisible))
            $inputVisible = explode(',', $inputVisible);

        $outputFields = [];
        foreach ($resource->fields as $field) {
            if ($field->output)
                $outputFields[] = $field->name;
        }

        if ($inputVisible === null) {
            return array_intersect($model->getRequestedAttributes(), $outputFields);
        }

        return array_intersect($model->getRequestedAttributes(), array_intersect($outputFields, $inputVisible));
    }
}