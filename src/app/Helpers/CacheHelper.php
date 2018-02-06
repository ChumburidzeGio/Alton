<?php
/**
 * User: Roeland Werring
 * Date: 13/09/17
 * Time: 20:44
 *
 */

namespace App\Helpers;

use App\Models\Field;
use App\Models\Resource;
use ArrayObject;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;


class CacheHelper
{
    const TAG_PARTNER           = 'partner';
    const TAG_PARTNER_COMPONENT = 'partner_component';
    const TAG_PARTNER_NODE      = 'partner_node';
    const TAG_PARTNER_PRESET    = 'partner_preset';
    const TAG_PARTNER_REVISION  = 'partner_revision';
    const TAG_PARTNER_WEBSITE   = 'partner_website';

    protected $local;

    protected static $instance;
    const RESOURCE2_CACHE_DRIVER = 'mongodb';

    /**
     * Put all application wide cache keys here
     */
    const KEY_RESOURCE_ALL = 'resource.all';

    private static $MEM_CACHED_ENABLED = 0;
    const CACHE_MINUTES = 60;

    /**
     * @return static
     */
    public static function instance()
    {
        if(!static::$instance) {
            static::$instance = new static();
        }
        //        if (Config::get('app.debug') == true) {
        //            self::$MEM_CACHED_ENABLED = 0;
        //        }


        return static::$instance;
    }

    /**
     * @param callable $callback
     * @param array $arguments
     * @param array $keys
     *
     * @return mixed
     */
    public static function call(Callable $callback, Array $arguments, Array $keys = [])
    {
        //Check if skipcache is enabled
        $skipcache = isset($arguments[2], $arguments[2]['skipcache']);


        // Use this as the local cache key
        $key = md5(json_encode($keys ?: $arguments));

        // Return early if local cache exists
        if(static::instance()->has($key)){
            return static::instance()->get($key);
        }


        if(self::$MEM_CACHED_ENABLED && $skipcache){
            Cache::tags('resource', 'resource.all')->forget('resource.all.' . $key);
        }
        //
        if(self::$MEM_CACHED_ENABLED){
            $cache = Cache::tags('resource', 'resource.all')->get('resource.all.' . $key);
            if($cache){
                cw('mem mache hit');
                return $cache;
            }
        }

        $result = call_user_func_array($callback, $arguments);

        static::instance()->set($key, $result);

        if(self::$MEM_CACHED_ENABLED){
            cw('memcache store');
            Cache::tags('resource', 'resource.all')->put('resource.all.' . $key, $result, self::CACHE_MINUTES);
        }

        return $result;
    }

    public function set($key, $value)
    {
        $this->local[$key] = $value;
        return $this;
    }

    public function has($key)
    {
        return isset($this->local[$key]);
    }

    public function get($key)
    {
        return $this->local[$key];
    }

    /**
     * This caches the result of the callback forever. It can be very handy if
     * you have a big object that needs to be cached.
     *
     * @param string $name
     * @param string $class
     * @param callable $callback
     * @return mixed
     */
    public static function rememberForever($name, $class, Closure $callback)
    {
        // Things were getting messy when we try to run artisan commands with things from the cache
        if(App::runningInConsole()) {
            $instance = App::make($class);
            return call_user_func($callback, $instance);
        }

        /**
         * WTF WTF?
         */
        // Cache::forget($name);
        $object = Cache::rememberForever($name, function() use ($class, $callback) {
            $instance = App::make($class);
            return call_user_func($callback, $instance);
        });

        // Replace the object from the cache with the one in the IoC container
        App::instance($class, $object);
    }

    /*
     * Resource 2 cache helper
     *
     */

    public static function processCache(Resource $resource, ArrayObject $input, $action = 'index')
    {
        return false;
        /**
         * Only cachable resources
         */
        if( ! $resource->hasBehaviour(Resource::BEHAVIOUR_CACHABLE) || isset($input['debug'])){
            return false;
        }

        $cacheKey = self::createCacheKey($resource, $input);

        $cache =  Cache::driver(self::RESOURCE2_CACHE_DRIVER)->get($cacheKey);
        cw('process cache '.$cacheKey);
        if($cache == null){
            cw('not in cache');
            return false;
        }
        cw('cache hit!');

        return $cache;
    }

    public static function storeCache(Resource $resource, $input, $result)
    {
        return false;
        if (!$resource->hasBehaviour(Resource::BEHAVIOUR_CACHABLE) || isset($input['debug'])) {
            return false;
        }
        //store for one day
        $cacheKey = self::createCacheKey($resource, $input);
        cw('store cache '.$cacheKey);
        Cache::driver(self::RESOURCE2_CACHE_DRIVER)->put($cacheKey, $result, 60 * 24);
    }

    /**
     * @param Resource $resource
     * @param ArrayObject $input
     *
     * @return mixed
     */
    private static function createCacheKey(Resource $resource, ArrayObject $input)
    {
        // Create cachekey, only in input fields
        $inputName = [];
        foreach($resource->inputs as $inputfield){
            $inputName[] = $inputfield->name;
        }
        $cacheKeyArr = Arr::only($input->getArrayCopy(), $inputName);
        $cacheKeyArr = array_except($cacheKeyArr, ['user', 'website']);

        // Don't forget the resource name
        $cacheKeyArr['__resource__'] = $resource->name;

        // Sort to make keys uniform
        ksort($cacheKeyArr);

        $cacheKey = sha1(str_replace('-', '', strtolower(json_encode($cacheKeyArr, JSON_NUMERIC_CHECK))));
        return $cacheKey;
    }

}