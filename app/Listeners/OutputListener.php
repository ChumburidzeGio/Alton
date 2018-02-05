<?php

namespace App\Listeners\Resources2;

use App\Helpers\EventHelper;
use App\Models\Resource;
use ArrayObject;

class OutputListener
{

    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
        $events->listen('resource.collection.before', [$this, 'split']);
    }

    /**
     * Split array based on input
     *
     * @param Resource $resource
     * @param ArrayObject $input
     * @param ArrayObject $collection
     * @param ArrayObject $resolved
     * @param $action
     * @param null $id
     */
    public static function split(Resource $resource, ArrayObject $input, ArrayObject $collection, ArrayObject $resolved, $action, $id = null)
    {
        if (!empty($input[OptionsListener::OPTION_USE_PLAN]))
            return;

        cws(__METHOD__ . ' - ' . $resource->name);
        foreach($resource->fields as $field) {
            //search for split field
            if(!$field->hasFilter('split')) continue;

            //if input is not array, no split
            if (!is_array($input[$field->name])) continue;

            $returnArray = [];
            foreach($collection as $entry) {
                foreach($input[$field->name] as $splitField) {
                    $entry[$field->name] = $splitField;
                    $returnArray[] = $entry;
                }
            }
            $collection->exchangeArray($returnArray);
        }
        cwe(__METHOD__ . ' - ' . $resource->name);
    }

}