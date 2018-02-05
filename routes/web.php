<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/', function () {
    return 'Awesomeness is coming shortly!';
});

$router->group(['prefix' => 'v1'], function($router)
{
    $router->patch('{resource2}', ['as' => 'resource.map', 'uses' => 'ResourceController@map']);
    $router->get('{resource2}', ['as' => 'resource.map', 'uses' => 'ResourceController@indexResource']);

    $router->delete('{resource2}/data', ['as' => 'resource.truncate', 'uses' => 'ResourceController@truncate']);
    $router->get('{resource2}/data', ['as' => 'resource.index', 'uses' => 'ResourceController@index']);
    $router->get('{resource2}/data/{id}', ['as' => 'resource.show', 'uses' => 'ResourceController@show']);
    $router->post('{resource2}/data', ['as' => 'resource.store', 'uses' => 'ResourceController@store']);
    $router->put('{resource2}/data/{id}', ['as' => 'resource.update', 'uses' => 'ResourceController@update']);
    $router->delete('{resource2}/data/{id}', ['as' => 'resource.destroy', 'uses' => 'ResourceController@destroy']);

});



