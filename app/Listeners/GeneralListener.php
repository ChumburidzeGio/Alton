<?php


namespace App\Listeners\Resources2;

use App\Interfaces\ResourceInterface;
use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Events\Dispatcher;
use App;
use Komparu\Document\Contract\Options;

class GeneralListener
{
    private static $cache = [];

    //'resource.process.after'
    public function subscribe(Dispatcher $events)
    {
        //$events->listen('resource.process.input', [$this, 'setLanguageConditions']);
        $events->listen('resource.process.input', [$this, '_limit']);
        $events->listen('resource.process.after', [$this, '_sort']);
    }

    public function setLanguageConditions($resource, $input)
    {
        if (isset($input['website'], $input['user'])) {
            app('translator')->setConditions([
                'user'    => $input['user'],
                'website' => $input['website']
            ]);
        }
    }

    public function _limit(Resource $resource, ArrayObject $input)
    {
        if ($input->offsetExists(ResourceInterface::__ID)) {
            $input->offsetSet('_limit', 9999);
        }
    }

    public function _sort(Resource $resource, ArrayObject $input, ArrayObject $output)
    {
        // check if input has order field
        if (!$input->offsetExists('_order')) {
            return;
        }

        // get that field “s”
        if (!isset(self::$cache[$resource->id])) {
            self::$cache[$resource->id] = Field
                ::where('resource_id', $resource->id)
                ->whereIn('name', explode(Options::SORT_DELIMITER, $input['_order']))
                ->get()->toArray();
        }
        $fields = self::$cache[$resource->id];

        // check if it exists
        if (empty($fields)) {
            return;
        }

        $has_from = array_reduce($fields, function ($carry, $field) {
            return $carry or !is_null($field['from']);
        }, false);

        if (!$has_from) {
            return;
        }

        $keys = explode(Options::SORT_DELIMITER, $input['_order']);

        $directions = array_map(function ($direction) {
            return $direction === 'asc' ? 1 : -1;
        }, explode(Options::SORT_DELIMITER, array_get($input->getArrayCopy(), '_direction', 'asc')));

        $full_directions = array_merge(
            $directions,
            array_fill(0, count($keys) - count($directions), 1)
        );

        $modifiers = array_combine(
            $keys,
            $full_directions
        );

        $output->uasort(function ($a, $b) use ($modifiers) {
            foreach ($modifiers as $key => $modifier) {
                $value = ($a[$key] - $b[$key]) * $modifier;
                if ($value !== 0) {
                    return $value;
                }
            }

            return 0;
        });
    }
}