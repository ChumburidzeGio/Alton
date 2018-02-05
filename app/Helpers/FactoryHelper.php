<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;


/**
 * User: Roeland Werring
 * Date: 19/05/17
 * Time: 16:44
 *
 *
 * To prevent double querying all kind of stuff
 *
 */

class FactoryHelper
{


    /**
     * Generic model cache
     */
    private static $cache;

    public static function retrieveModel($name, $field, $value, $multi = false, $orFail = false)
    {
        cw('retrieve model '.$name);
        $key = json_encode(['name' => $name, 'field' => $field, 'value' => $value, 'multi' => $multi]);
        if( ! isset(self::$cache[$key])){
            $model = App::make($name);
            if($multi){
                self::$cache[$key] = $model->where($field, $value)->get();
            }else{
                self::$cache[$key] = $model->where($field, $value)->first();
            }
            cw('FactoryHelper store ' . $name . ": " . $field . ' => ' . $value);
        }

        if ($orFail && !self::$cache[$key])
            throw new ModelNotFoundException($name);

        return self::$cache[$key];
    }


    /**
     * All under here in progress
     */

    private static $resourceCache = null;

    public static function getResourceFields($resource_id)
    {
        //        self::getResourceCache();
        //        dd(self::getResourceCache()['map']['resource_id_fields'][$resource_id]);
        // dd(self::getResourceCache()['map']['resource_id_fields']);
        //        $fieldIds = self::getResourceCache()['map']['resource_id_fields'][$resource_id];
        $fieldIds = self::getResourceCache()['map']['resource_id_fields'][$resource_id];
        return self::getField($fieldIds);
    }

    public static function getField($mixed)
    {
        if( ! is_array($mixed)){
            return self::getResourceCache()['fields'][$mixed];
        }
        $return = [];
        foreach($mixed as $fieldId){
            $return[] = self::getResourceCache()['fields'][$fieldId];
        }
        return $return;
    }

    //$resourceFields = FactoryHelper::retrieveModel('App\Models\Field', 'resource_id', $resource['id'], true);
    private static function getResourceCache()
    {
        try{

            if( ! self::$resourceCache){
                $cache = Cache::driver('mongodb')->get('resource2totalcache');
                if( ! $cache){

                    cws('loading_resource_cache');
                    self::$resourceCache = [];
                    $resources           = Resource::all();
                    foreach($resources as $resource){
                        self::$resourceCache['resources'][$resource->id]               = $resource;
                        self::$resourceCache['map']['resources_name'][$resource->name] = $resource->id;
                    }
                    $fields = Field::all();

                    foreach($fields as $field){

                        self::$resourceCache['fields'][$field->id]                               = $field;
                        self::$resourceCache['map']['resource_id_fields'][$field->resource_id][] = $field->id;

                        $toFields = $field->toFields;
                        foreach($toFields as $toField){
                            self::$resourceCache['map']['field_to_fields'][$field->id][] = $toField->id;
                        }
                        $fromFields = $field->fromFields;
                        foreach($fromFields as $fromField){
                            self::$resourceCache['map']['field_from_fields'][$field->id][] = $fromField->id;
                        }
                    }
                    $size = strlen(serialize( self::$resourceCache));
                    cwe('loading_resource_cache');
                    Cache::driver('mongodb')->put('resource2totalcache', 60 * 24, serialize(self::$resourceCache));
                } else {
                    self::$resourceCache = $cache;
                }


            }
            return self::$resourceCache;
        }catch(\Exception $exception){
            dd($exception->getLine() . ' - ' . $exception->getMessage());
        }
    }
}