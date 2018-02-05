<?php

namespace App\Listeners\Resources2;

use App\Helpers\CacheHelper;
use App\Helpers\DocumentHelper;
use App\Helpers\ResourceHelper;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;
use Input;
use Komparu\Document\Contract\Response;
use Komparu\Value\ValueInterface;
use Cache;

class ModelListener
{
    /**
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe($events)
    {
//        $events->listen('eloquent.creating: App\Models\Resource', [$this, 'patch']);
        $events->listen('eloquent.saved: App\Models\Resource', [$this, 'clearCache']);

        $events->listen('resource.copy.after', [$this, 'patch']);
        $events->listen('resource.copy.after', [$this, 'data']);
    }

    /**
     * Patch the new resource directly after creating it.
     *
     * @param Resource $new
     * @param Resource $original
     * @throws \Exception
     */
    public static function patch(Resource $new, Resource $original = null)
    {
        // Only have to patch REST resources
        if($new->act_as != 'rest') return;

        // First add the resource to the document registry and patch it
        ResourceHelper::addToDocumentRegistry($new, $patch = true);
    }

    /**
     * Also copy the data next to the structure.
     *
     * @param Resource $new
     * @param Resource $original
     */
    public function data(Resource $new, Resource $original = null)
    {
        list($index, $type) = explode('.', $original->name);

        /** @var Response $data */
        $data = DocumentHelper::get($index, $type, ['limit' => 9999]);

        list($index, $type) = explode('.', $new->name);

        array_map(function($item) use($index, $type) {

            unset($item['__id']);
            unset($item['created_at']);
            unset($item['updated_at']);
            unset($item['__type']);
            unset($item['__index']);

            DocumentHelper::insert($index, $type, $item);

        }, $data->documents()->toArray());

    }

    /**
     * This will clear the cache that is used at bootstrap. It contains all resources
     * with all fields. So if a resource is added or changed, we need to rebuild the cache.
     */
    public function clearCache()
    {
        Cache::forget(CacheHelper::KEY_RESOURCE_ALL);
    }

}