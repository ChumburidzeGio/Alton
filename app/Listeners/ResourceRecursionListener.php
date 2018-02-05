<?php

namespace App\Listeners\Resources2;

use App\Exception\NoMatchingChildResourceData;
use App\Exception\ServiceError;
use App\Helpers\CacheHelper;
use App\Helpers\FieldHelper;
use App\Helpers\ResourceHelper;
use App\Interfaces\ResourceInterface;
use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Komparu\Document\ArrayHelper;

class ResourceRecursionListener
{
    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.process.propagated', [$this, 'process']);
    }

    public static function process(Resource $resource, ArrayObject $input, ArrayObject $data, $action, $id = null)
    {
        // Get info on the resource and its children
        Event::until('resource.process', [$resource, $input, $data, $action, $id]);

        if ($data->count() == 0)
            return [];

        // Performance improvement, these are all needed.
        $resource->load(['fields', 'fields.resource', 'fields.fromFields.resource', 'fields.toFields.resource']);

        $info = self::info($resource, $input->getArrayCopy(), $action);

        $resolved = new ArrayObject();

        //Check to see if there are any resources that can be handled in parallel based on the resource_name found in the
        //processed data.
        $handled = self::handleParallelism($resource, $data, $input, $resolved, $info, $id);
        $info    = $handled['info'];
        if (isset($handled['parallel'])) {
            $resolved->offsetSet('parallel', $handled['parallel']);
        }

        // Call the child resources that have input information that comes from
        // the output from the current resource
        foreach ($info['after'] as $alias => $child) {

            // Get the params we need to send to the child resource
            $params = self::buildParamsForChild($resource, $child['resource'], $input->getArrayCopy(), $child['mapping']);
            // Setup the params that are needed to call the child resource.
            // Some child fields need params from the parent output. And thats
            // why these children need to be called after the parent resource.
            $params = self::mapParamsForChild($resource, $child['resource'], $params, $data);


            // For now, we only need to call child resources if there are params given.
            // This assumes that there every child resources needs some kind of input params,
            // but we can live for that for the time being.
            // @todo Allow child resources without input params to be called from parent.
            $resolved[$alias] = $params
                ? ResourceHelper::call($child['resource'], $child['action'], $params, $child['id'])
                : [];
        }


        // See if we need to display a collection or a single record

        $collection = ($action == 'index' && (in_array($resource->act_as, [Resource::ACT_AS_REST, Resource::ACT_AS_ELOQUENT_REST, Resource::ACT_AS_SERVICE_REST]))) || $resource->act_as == 'collection';

        if ($action == 'bulk') {
            $result = $data;
        } else {
            $result = $collection
                ? self::mapCollection($resource, $data, $resolved, $input, $action, $id)
                : self::mapRow($resource, $data, $resolved, $input, $action, $id);
        }

        return $result;
    }


    /**
     * Get the info that is needed to perform the actual resource call.
     *
     * @param Resource $resource
     * @param array $input
     * @param string $action
     *
     * @return array
     */
    public static function info(Resource $resource, array $input, $action = 'index')
    {

        return CacheHelper::call(function (Resource $resource, $input, $action) {

            $mapping = [];
            $from    = [];
            $after   = [];
            $rules   = [];
            $filters = [];

            // Check if we need to see only a subset of fields. This has impact on
            // the number of child resources we might or might not have to call.
            $visible = ResourceHelper::getVisible($resource, $input);

            // Do another round after all To mappings are collected.
            // Then we have enough information to build params for
            // child resources.
            $resource->load(['fields', 'fields.resource', 'fields.fromFields.resource', 'fields.toFields.resource']);
            foreach($resource->fields as $field){

                $name           = $field->name;
                $rules[$name]   = $field->rules;
                $filters[$name] = $field->filters;

                // We want to know to which resources we need to send data
                foreach($field->toFields as $toField){
                    $alias                           = $toField->resource->id;
                    $mapping[$alias][$toField->name] = $name;

                }
            }

            foreach($resource->fields as $field) {

                // If we have restricted visibility, then check if we need to
                // call a child resource. If the field is not visible, we don't
                // have to call it.
                if($visible && ! in_array($field->name, $visible)){
                    continue;
                }

                // We want to know to which resources we need to send data
                foreach($field->fromFields  as $fromField){
                    $alias                                    = $fromField->resource->id;
                    $from[$alias]['id']                       = null;
                    $from[$alias]['action']                   = 'index';
                    $from[$alias]['resource']                 = $fromField->resource;
                    $from[$alias]['fields'][$fromField->name] = $field->name;
                    $from[$alias]['mapping']                  = array_key_exists($alias, $mapping) ? $mapping[$alias] : [];
                }

            }
            // Split the relations in before/after the current resource call.
            foreach($from as $alias => $relation){
                $after[$alias] = $relation;
            }

            // This is the info we want to return
            $result = compact('after', 'rules', 'filters');
            return $result;

        }, func_get_args(), [
            'resource' => $resource->id,
            'input'    => $input,
        ]);
    }


    /**The function will check for and handle child resources in parallel based on the resource_name
     *
     * @param Resource $resource
     * @param ArrayObject $data
     * @param ArrayObject $input
     * @param ArrayObject $resolved
     * @param array $info
     * @param null $id
     *
     * @return array
     */
    private static function handleParallelism(Resource $resource, ArrayObject $data, ArrayObject $input, ArrayObject $resolved, array $info, $id = null)
    {
        cws('parallel_process.' . $resource->name, 'Parallel processing for ' . $resource->name);
        $returnArray['info'] = $info;
        if( ! $resource->hasBehaviour(Resource::BEHAVIOUR_PARALLEL)){
            cw('Resource ' . $resource->name . ' has no Parallel behavior configured');
            cwe('parallel_process.' . $resource->name);
            return $returnArray;
        }
        //Collect the headers necessary
        $headers['X-Auth-Token']  = Request::header('X-Auth-Token');
        $headers['X-Auth-Domain'] = Request::header('X-Auth-Domain');
        $headers['Accept-Language'] = Request::header('Accept-Language');

        //Chunk the data for doing in parallel
        if(ArrayHelper::isAssoc($data->getArrayCopy())){
            cwe('parallel_process.' . $resource->name);
            return $returnArray;
        }
        $resourceDataChunks = static::prepareChunks($data);


        if(count($resourceDataChunks) > 1){
            cw("Loading THREADS: " . count($resourceDataChunks));

            //We have more than one resource name on a resource with the parallel
            //behavior. Split the input params and send it in parallel
            $params    = [];
            $resources = new ArrayObject();
            foreach($resourceDataChunks as $name => $resourceData){
                $child = Resource::where('name', $resourceData['resource'])->first();
                if( ! $child){
                    cw('could not find child! ' . $resourceData['resource']);
                    continue;
                }
                // Get the params we need to send to the child resource
                foreach($info['after'] as $key => $resourceInfo){
                    if($resourceInfo['resource']->id == $child->id){
                        //Use the mapping for building the child parameters
                        $params[$name] = static::buildParamsForChild($resource, $child, $input->getArrayCopy(), $resourceInfo['mapping']);

                        $params[$name] = static::mapParamsForChild($resource, $child, $params[$name], new ArrayObject($resourceData['data']));
                        if(isset($resourceData['extraInput'])){
                            foreach($resourceData['extraInput'] as $inputKey => $inputValue){
                                $params[$name][$inputKey] = $inputValue;
                            }
                        }
                        $resources->offsetSet($name, $resourceInfo['resource']);
                    }
                }
            }
            $params             = new ArrayObject($params);
            $resourceDataChunks = new ArrayObject($resourceDataChunks);
            //We have built our input parameters so we can now fire the event that will create the requests!
            Event::fire('resource.process.parallel', [$resources, $params, $resourceDataChunks, $headers, 'index', $resource]);

            //Add the parallel data into the resolved array
            $returnArray['parallel'] = $resourceDataChunks->getArrayCopy();
            foreach($info['after'] as $key => $value){
                // Remove any 'resource.name' resource mappings
                // (they do not need to be called, or will be called in the parallel listener)
                $mappingValue = ResourceInterface::RESOURCE_NAME;
                if($value['resource']->hasBehaviour(Resource::BEHAVIOUR_SPLITABLE_BY_FIELD)){
                    $fieldOfInterest = $value['resource']->fields()->where('filters', Field::FILTER_USE_FOR_CHUNKING)->first();
                    $mappingValue = $fieldOfInterest->name;
                }
                if(isset($value['mapping'][$mappingValue])){
                    unset($info['after'][$key]);
                }
            }
        }else{
            foreach($info['after'] as $key => $value){
                // Remove any 'resource.name' resource mappings that are not mapped to a chunk
                if(isset($value['mapping'][ResourceInterface::RESOURCE_NAME])){
                    foreach($resourceDataChunks as $resourceDataChunk){
                        if($resourceDataChunk['resource'] == $value['resource']->name){
                            continue 2;
                        }
                    }

                    unset($info['after'][$key]);
                }
            }
        }
        $returnArray['info'] = $info;
        cwe('parallel_process.' . $resource->name, 'Parallel processing for ' . $resource->name);
        return $returnArray;
    }


    /**
     *
     * Here we determine what parts of the original input we want to pass thru
     * to the child. The basic rules here are
     *
     * 1. Pass thru all input
     * 2. Except if the field exists in the child
     * 3. But allow it if there is an explicit input mapping
     *
     * @param Resource $parent
     * @param Resource $child
     * @param array $input
     * @param array $mapping
     *
     * @return array
     */
    public static function buildParamsForChild(Resource $parent, Resource $child, Array $input, Array $mapping)
    {
        /** @var Field[] $fields */
        $fields = collect($parent->fields)->keyBy('name');


        $params = [];

        // Get the child field names, to see if we must pass thru or
        // filter some original input fields
        $fieldNames = $child->fields->fetch('name')->toArray();

        foreach($input as $key => $value){

            // Don't pass through reserved params
            if(in_array($key, ResourceHelper::getReservedParamNames())){
                continue;
            }

            // If the input is in the child field names, then we should not pass this thru...
            if(in_array($key, $fieldNames)){
                continue;
            }

            $params[$key] = $value;
        }

        // ... unless there is a specific input mapping.
        foreach($mapping as $childFieldName => $parentFieldName){

            // Get the default value from the parent field
            $default = $fields[$parentFieldName]->default;

            // Only pass thru inputs to child resource if there are values
            $value = Arr::get($input, $parentFieldName, $default);

            // Nest the value in the params
            if(null !== $value){
                ArrayHelper::set($params, $childFieldName, $value);
            }
        }

        return $params;
    }



    /**
     * @param Resource $parent
     * @param Resource $child
     * @param array $params
     * @param ArrayObject $data
     *
     * @return array
     */
    public static function mapParamsForChild(Resource $parent, Resource $child, Array $params, ArrayObject $data)
    {
        $output       = $data->getArrayCopy();
        $isCollection = ! ArrayHelper::isAssoc($output);

        if($isCollection){
            $output = array_map(function ($row) {
                return Arr::dot($row);
            }, $output);
        }

        /** @var Field $field */
        foreach($parent->fields as $field){

            // We can only pass output data to input for child resource if
            // the field is an output. And the field must be mapped to a
            // child resource
            if( ! $field->output || ! $field->toFields->count()){
                continue;
            }

            // Find params in the output of the parent
            $params = static::findParamsForChild($field, $child, $params, $output);
        }

        return $params;
    }


    /**
     * @param Field $field
     * @param Resource $child
     * @param array $params
     * @param array $output
     *
     * @return array
     */
    public static function findParamsForChild(Field $field, Resource $child, Array $params, Array $output)
    {
        foreach($field->toFields as $to){

            // Only use the params for the right child resource
            if($to->resource->id != $child->id){
                continue;
            }

            // If there already is a param set, then we don't have to
            // find it from the output. Just use that one from the
            // original input params.
            if(isset($params[$field->name])){
                continue;
            }

            // Get the value from the output
            try{
                $param = static::getFromData($output, $field->name);
                if (is_array($param)) {
                    $param = array_values(array_unique($param));

                    if (in_array(Field::FILTER_COMMA_SEPARATED_INPUT_ALLOWED, $to->filters)) {
                        $param = implode(',', $param);
                    }
                }
            }catch(\Exception $e){

                // We will catch a non-existing output value for now.
                // When calling the child resource, it is up to their validation rules to decide
                // if the input can be null or that it is required.
                $param = null;
            }

            // Only overwrite the existing param if there is an actual value
            if($param){
                ArrayHelper::set($params, $to->name, $param);
            }
        }

        return $params;
    }


    /**
     * @param array $data
     * @param string $key
     *
     * @return array|mixed|null
     */
    public static function getFromData(Array $data, $key)
    {
        // Not a collection, then just return one value
        if(ArrayHelper::isAssoc($data)){
            return Arr::get($data, $key);
        }

        // Only use one key from the collection
        $mapped = array_map(function ($item) use ($key) {
            return Arr::get($item, $key);
        }, $data);

        // Skip empty null values from the collection
        return Arr::where($mapped, function ($key, $value) {
            return ! is_null($value);
        });
    }



    /**
     *
     * The output of a 'before' child call, we want to pass through to the main call
     *
     * To fields could be removed (TODO)
     * from fields need to be copied to the input
     *
     * @param Resource $parent
     * @param Resource $child
     * @param array $params
     * @param array $mapping
     *
     * @return array
     */
    public static function buildParamsForParent(Resource $parent, Resource $child, Array $params, Array $toFields, Array $fromFields, Array $output)
    {

        //        /** @var Field[] $fields */
        $fields = collect($parent->fields)->keyBy('name');

        $retParams = [];

        foreach($params as $key => $value){

            // Don't pass through reserved params
            if(in_array($key, ResourceHelper::getReservedParamNames())){
                continue;
            }

            // If the input is in the to field names, then we should not pass this thru...
            if(in_array($key, $toFields)){
                continue;
            }

            $retParams[$key] = $value;
        }

        // ... unless there is a specific input mapping.
        foreach($fromFields as $fromFieldName => $parentFieldName){

            // Get the default value from the parent field
            $default = $fields[$parentFieldName]->default;

            // Only pass thru inputs to child resource if there are values in the output
            $arr = Arr::pluck($output, $fromFieldName, $default);

            // Nest the value in the params
            if( ! empty($arr)){
                ArrayHelper::set($retParams, $fromFieldName, $arr);
            }
        }
        return new ArrayObject($retParams);
    }


    /** Chunk the return data of a resource according to the resource_name field. The field
     *  denotes the child resource that a row depends on.
     *  If any child resource is Splitable it is chunked further for enhanced performance.
     *
     * @param ArrayObject $data
     *
     * @return array
     */
    private static function prepareChunks(ArrayObject $data)
    {
        $arrayData          = $data->getArrayCopy();
        $resourceDataChunks = [];
        $counter            = 0;
        foreach($arrayData as $row){
            $alias                                  = $row['resource']['name'];
            $resourceDataChunks[$alias]['data'][]   = $row;
            $resourceDataChunks[$alias]['resource'] = $row['resource']['name'];
            $counter ++;
        }
        foreach($resourceDataChunks as $alias => $resourceDataChunk){
            //Check if the resource is splittable!
            $aliasResource = Resource::where('name', $alias)->first();
            if($aliasResource != null && $aliasResource->hasBehaviour(Resource::BEHAVIOUR_SPLITABLE)){
                $counter = 0;
                $mod     = min(count($resourceDataChunk['data']), 3);

                foreach($resourceDataChunk['data'] as $row){
                    //TODO: Move the 3 from here into a meta column for better configuration
                    $newAlias                                  = $row['resource']['name'] . ($counter % $mod);
                    $resourceDataChunks[$newAlias]['data'][]   = $row;
                    $resourceDataChunks[$newAlias]['resource'] = $row['resource']['name'];
                    $counter ++;
                }
                unset($resourceDataChunks[$alias]);
            }elseif($aliasResource != null && $aliasResource->hasBehaviour(Resource::BEHAVIOUR_SPLITABLE_BY_FIELD)){
                //Get the resource to call and the field to split by
                $aliasResource   = Resource::where('name', $alias)->first();
                $fieldOfInterest = $aliasResource->fields()->where('filters', Field::FILTER_USE_FOR_CHUNKING)->first();

                //Go through the results and place them in buckets according to the fieldOfInterest
                foreach($resourceDataChunk['data'] as $row){
                    if(isset($row[$fieldOfInterest->name])){
                        $newAlias = $row[$fieldOfInterest->name];
                        if( ! isset($resourceDataChunks[$newAlias])){
                            $resourceDataChunks[$newAlias]             = [];
                            $resourceDataChunks[$newAlias]['data']     = [];
                            $resourceDataChunks[$newAlias]['resource'] = $row['resource']['name'];
                            //Set extraInput that will overwrite the input parameters for the subsequent
                            //resource call
                            $resourceDataChunks[$newAlias]['extraInput'] = [$fieldOfInterest->name => $newAlias];
                        }
                        $resourceDataChunks[$newAlias]['data'][] = $row;
                    }
                }
                unset($resourceDataChunks[$alias]);
            }
        }
        return $resourceDataChunks;
    }


    /**
     * Once data is received from the child resource, we need to merge it back to the output.
     *
     * If the data is a collection and the output is a collection, we need to map these based
     * on some matching data. For now, we match it by searching for 'to' fields with the same
     * resource as the 'from' field.
     *
     * @param Resource $parent
     * @param Resource $child
     *
     * @return array
     */
    public static function getMatchingInputs(Resource $parent, Resource $child, $mustBeKey = false)
    {
        return CacheHelper::call(function (Resource $parent, Resource $child, $mustBeKey = false) {

            $matches = [];

            foreach($parent->fields as $field){

                // We can only map to the output if this field also is an output
                //            if(!$field->output) continue;

                foreach($field->toFields as $to){

                    // If there are toFields, only use the ones with the same child resource
                    if($to->resource->id != $child->id){
                        continue;
                    }

                    $matches[] = compact('field', 'to');
                }
            }

            return $matches;

        }, func_get_args(), [
            'parent'    => $parent->id,
            'child'     => $child->id,
            'mustBeKey' => $mustBeKey,
        ]);

    }

    /**
     * Once data is received from the child resource, we need to merge it back to the output.
     *
     * If the data is a collection and the output is a collection, we need to map these based
     * on some matching data. For now, we match it by searching for 'to' fields with the same
     * resource as the 'from' field.
     *
     * @param Resource $parent
     * @param Resource $child
     *
     * @return array
     */
    public static function getMatchingIndexedInputs(Resource $parent, Resource $child)
    {
        $matches = static::getMatchingInputs($parent, $child);

        // We only want to match if the child field is at least a key.
        // This can be a primary key or just a regular key.
        return array_values(array_filter($matches, function ($match) {
            return $match['to']->isKey;
        }));
    }

    /**
     * @param Resource $parent
     * @param Resource $child
     * @param array $parentData
     * @param array $childData
     * @param Field $field
     *
     * @return array|null
     * @throws NoMatchingChildResourceData
     */
    public static function matchChildOutput(Resource $parent, Resource $child, array $parentData, array $childData, Field $field)
    {
        // Get matching fields from the parent resource and the child, based on the parent 'to' fields.

        $matchesOnKey = self::getMatchingIndexedInputs($parent, $child);

        // If there were no matches, then we don't have to mix and match parent and child values.
        // We can just return the child data as a subset of the parent.
        if( ! $matchesOnKey){
            return $childData;
        }

        $map = [];

        foreach($matchesOnKey as $matching){

            // We only want to match if the child field is at least a key.
            // This can be a primary key or just a regular key.
            if( ! $matching['to']->isKey){
                continue;
            }
            $map[$matching['to']->name] = Arr::get($parentData, $matching['field']->name);
        }

        foreach($childData as $item){

            $found = [];
            foreach($map as $toName => $value){

                if($value != Arr::get($item, $toName)){
                    //If the field that could not be found is the resource.id then no need to keep going
                    if($toName == ResourceInterface::RESOURCE_ID){
                        break 1;
                    }
                    continue;
                }

                $found[] = $toName;
            }

            // If the child data item is not null or empty, return the full child item
            // Otherwise there still is a match, but match is null.
            if(count($found) == count($matchesOnKey)){
                return $item;
            }
        }


        // Return a null value if this is allowed for this field
        if($field->hasFilter(Field::FILTER_ALLOW_NULL)){
            return null;
        }

        // The data maybe corrupt at this point.
        // Throw an exception and handle this from the outside
        throw new NoMatchingChildResourceData($field, 'Cannot map `'. $field->name .'.'. $field->resource->name .'` from `'. $child->name .'`, mapping on '. json_encode($map));
    }

    /**
     * @param array $map
     * @param array $parentData
     * @param array $childData
     *
     * @return array|null
     */
    public static function matchData(Array $matchesOnKey, array $parentData, array $childData)
    {
        return CacheHelper::call(function (Array $matchesOnKey, array $parentData, array $childData) {

            $map = [];

            foreach($matchesOnKey as $matching){

                // We only want to match if the child field is at least a key.
                // This can be a primary key or just a regular key.
                if( ! $matching['to']->isKey){
                    continue;
                }

                $map[$matching['to']->name] = Arr::get($parentData, $matching['field']->name);
            }

            foreach($childData as $item){

                $found = [];
                foreach($map as $toName => $value){

                    if($value != Arr::get($item, $toName)){
                        continue;
                    }

                    $found[] = true;
                }

                // If the child data item is not null or empty, return the full child item
                // Otherwise there still is a match, but match is null.
                if(count($found) == count($map)){
                    return $item;
                }
            }

        }, func_get_args(), compact('parentData', 'childData'));
    }


    /**
     * @param Resource $resource
     * @param array $collection
     * @param ArrayObject $resolved
     * @param ArrayObject $input
     * @param string $action
     * @param string|int $id
     *
     * @return array
     */
    public static function mapCollection(Resource $resource, ArrayObject $collection, ArrayObject $resolved, ArrayObject $input, $action = 'index', $id = null)
    {
        Event::fire('resource.collection.before', [$resource, $input, $collection, $resolved, $action, $id]);
        Event::fire('resource.collection.' . $resource->name . '.before', [$resource, $input, $collection, $resolved, $action, $id]);
        $rows = [];
        $times = [];
        cws('Mapping whole collection ' . $resource->name);
        foreach($collection as $index => $data){
            try{
                cws('Mapping Row for ' . $resource->name . ' : ' . $index);
                $t = microtime(true);
                $rows[] = static::mapRow($resource, new ArrayObject($data), $resolved, $input, $index, $action, $id);
                $times[] = microtime(true) - $t;
                cwe('Mapping Row for ' . $resource->name . ' : ' . $index);
            }catch(NoMatchingChildResourceData $e){
                $times[] = microtime(true) - $t;
                cws('SKIPPING : ' . $index .' ('. json_encode(array_only($data, ['__id', 'title'])) .' - '. $e->getMessage() .')');
                cwe('SKIPPING : ' . $index .' ('. json_encode(array_only($data, ['__id', 'title'])) .' - '. $e->getMessage() .')');
                cwe('Process Output for Row: ' . $index);
                cwe('Mapping Row for ' . $resource->name . ' : ' . $index);
                // Skip this row in the collection, it is expected behaviour here.
            }catch(\Exception $e){
                cwe('Mapping whole collection ' . $resource->name);
                throw new ServiceError($resource, $input->getArrayCopy(), 'File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            }
        }
        cwe('Mapping whole collection ' . $resource->name, 'Mapping whole collection ' . $resource->name . ' ('. count($times).')');
        cw('Mapping time: ' . array_sum($times));
        if (count($times) > 0) {
            cw('Mapping average time: ' . (array_sum($times) / count($times)));
        }

        // Prepare rows for late modification thru events
        $collection->exchangeArray($rows);
        Event::fire('resource.collection.after', [$resource, $input, $collection, $resolved, $action, $id]);
        Event::fire('resource.' . $resource->name . '.collection.after', [$resource, $input, $collection, $resolved, $action, $id]);

        return $collection->getArrayCopy();
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $data
     * @param ArrayObject $resolved
     * @param ArrayObject $input
     * @param int $index
     * @param string $action
     * @param null $id
     *
     * @return array
     */
    public static function mapRow(Resource $resource, ArrayObject $data, ArrayObject $resolved, ArrayObject $input, $index = null, $action = 'index', $id = null)
    {
        // Do we only allow outputs that are fields (strict mode)?
        // Or do we pass thru other fields as well?
        $output = $resource->strict ? new ArrayObject() : new ArrayObject($data);

        Event::fire('resource.row.before', [$resource, $data, $resolved, $output, $input, $index, $id]);

        // Use the dotted version for inspecting
        $data = clone $data;
        // Allow granular modification at field level
        $fields = $resource->fields;
        foreach($fields as $field){
            static::mapField($field, $data, $resolved, $output, $input, $fields, $index);
        }



        // Allow late modification of the end result.
        // This will be invoked multiple times if the output is part of a collection.
        Event::fire('resource.row.after', [$resource, $input, $output, $action, $id]);
        return $output->getArrayCopy();
    }

    /**
     * @param Field $field
     * @param ArrayObject $data
     * @param ArrayObject $resolved
     * @param ArrayObject $output
     * @param ArrayObject $input
     * @param $matches
     */
    public static function mapField(Field $field, ArrayObject $data, ArrayObject $resolved, ArrayObject $output, ArrayObject $input, Collection $fields, $index = null)
    {
        if( ! $field->output){
            return;
        }
        //        cws(sprintf('%s - defaults : %s', $field->name, $hash));
        FieldHelper::defaults($field, $data, $resolved, $output);
        //        cwe(sprintf('%s - defaults : %s', $field->name, $hash));

        //        cws(sprintf('%s - data : %s', $field->name, $hash));
        FieldHelper::data($field, $data, $resolved, $output, $input, $fields);
        //        cwe(sprintf('%s - data : %s', $field->name, $hash));

        //        cws(sprintf('%s - merge : %s', $field->name, $hash));
        FieldHelper::merge($field, $data, $resolved, $output, $input, $index);
        //        cwe(sprintf('%s - merge : %s', $field->name, $hash));

        //        cws(sprintf('%s - transform', $field->name));
        FieldHelper::transform($field, $data, $resolved, $output, $input, $index);
        //        cwe(sprintf('%s - transform', $field->name));

        //        cws(sprintf('%s - typecast', $field->name));
        FieldHelper::typecast($field, $data, $resolved, $output);
        //        cwe(sprintf('%s - typecast', $field->name));
    }

    /**
     * @param $script
     * @param array $data
     * @param array $params
     *
     * @param bool $nullable
     *
     * @return null
     */
    public static function script($script, array $data, array $params, $nullable = false)
    {
        $data   = Arr::dot($data);
        $params = Arr::dot($params);

        // Replace variables with actual values from the document
        $script = static::replace($script, $data, $params, $nullable);
        if($script === null){
            return null;
        }
        $value = null;
        try{
            $function = create_function('$script', sprintf('return %s;', $script));
            return $function($script);
        }catch(\Exception $e){
            cw('Create function error: ' . sprintf('return %s;', $script));
            cw($e);
            //create function did not workout, return 0\

        }
        return 0.0;
    }


    /**
     * @param string $string
     * @param array $output
     * @param array $input
     * @param int $rounds
     * @param int $depth
     *
     * @param $nullable
     *
     * @return string
     * @throws \Exception
     */
    public static function replace($string, Array $output, Array $input, $nullable, $rounds = 10, $depth = 0)
    {
        // Just a quick performance check to see if we need to do something...
        if( ! strstr($string, '{')){
            return $string;
        }

        //        $debug = sprintf('Script Replace "%s" - hash: %s', $string, md5(json_encode([$output, $input, $rounds, $depth])));
        //        cws($debug);

        $pattern = '/{(input|output|):?([a-zA-Z0-9_\-.]+)}/';

        // Just a check to prevent nginx to crash...
        if($depth > $rounds){
            throw new \Exception(sprintf('Something went wrong replace values in script "%s".
                Probably a mistyped property name that does not exist.', $string));
        }

        // Find all tokens that need to be replaced
        //        cws('Preg match :' . md5(json_encode([$string, $output, $input, $rounds, $depth])));
        preg_match_all($pattern, $string, $matches);
        //        cwe('Preg match :' . md5(json_encode([$string, $output, $input, $rounds, $depth])));


        // Nothing to replace, than we're done!
        if( ! $matches[0]){
            //            cwe($debug);
            return $string;
        }

        foreach($matches[0] as $i => $token){

            $source = $matches[1][$i];
            $key    = $matches[2][$i];

            switch($source){

                case 'input':
                    $value = Arr::get($input, $key);
                    break;

                case 'output':
                    $value = Arr::get($output, $key);
                    break;

                default:
                    $value = Arr::get($output, $key, Arr::get($input, $key));
            }

            //convert booleans to numeric values for the script
            if(is_bool($value)){
                $value = ($value === false) ? 0 : 1;
            }

            // Just a check to prevent wrong eval with weird scripts
            if(is_null($value)){
                // if nullable, stop calculating we are done
                if($nullable){
                    return null;

                }
                $value = 0;
            }

            $string = str_replace($token, $value, $string);
        }

        //        cwe($debug);

        // Check here too, to prevent 1000+ unneeded function calls on some requests
        if( ! strstr($string, '{')){
            return $string;
        }

        // Try to replace nested variables...
        return static::replace($string, $output, $input, $nullable, $rounds, ++ $depth);
    }
}