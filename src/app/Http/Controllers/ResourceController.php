<?php

namespace App\Http\Controllers;

use App\Helpers\FactoryHelper;
use App\Helpers\ResourceHelper;
use App\Models\Resource;
use Illuminate\Support\Facades\Input;

class ResourceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }


    public function indexResource($resourceName) : Resource
    {
        cws('model_resource', 'Retreive the model');
        $resource = FactoryHelper::retrieveModel('App\Models\Resource', 'name', $resourceName, false, true);
        cwe('model_resource');
        return $resource;
    }


    public function map($resourceName)
    {
        $resource = $this->indexResource($resourceName);
        $resource->populateFields('map');
        $return = ResourceHelper::call($resource, 'map', Input::all());
        return response()->json($return)->header('X-Komparu-Resource', $resourceName);

    }

    public function index($resourceName)
    {
        $resource = $this->indexResource($resourceName);
        $resource->populateFields('index');
        $return = ResourceHelper::call($resource, 'index', Input::all());
        return response()->json($return)->header('X-Komparu-Resource', $resourceName);
    }

    public function show($id, $resourceName)
    {
        $resource = $this->indexResource($resourceName);
        $resource->populateFields('show');
        $return = ResourceHelper::call($resource, 'show', Input::all(), $id);
        return response()->json($return)->header('X-Komparu-Resource', $resourceName);
    }

    public function store($resourceName)
    {
        $resource = $this->indexResource($resourceName);
        $resource->populateFields('store');
        $return = ResourceHelper::call($resource, 'store', Input::all());
        return response()->json($return)->header('X-Komparu-Resource', $resourceName);
    }

    public function update($id, $resourceName)
    {
        $resource = $this->indexResource($resourceName);
        $resource->populateFields('update');
        $return = ResourceHelper::call($resource, 'update', Input::all(), $id);
        return response()->json($return)->header('X-Komparu-Resource', $resourceName);
    }

    public function destroy($id, $resourceName)
    {
        $resource = $this->indexResource($resourceName);
        $resource->populateFields('destroy');
        $return = ResourceHelper::call($resource, 'destroy', Input::all(), $id);
        return response()->json($return)->header('X-Komparu-Resource', $resourceName);
    }

    public function truncate($resourceName)
    {
        $resource = $this->indexResource($resourceName);
        $resource->populateFields('truncate');
        $return = ResourceHelper::call($resource, 'truncate', Input::all());
        return response()->json($return)->header('X-Komparu-Resource', $resourceName);
    }


}
