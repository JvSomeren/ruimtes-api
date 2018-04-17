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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'resources'], function () use ($router) {
    $router->get('', ['uses' => 'ResourceController@getAllResources']);
    $router->get('{id}', ['uses' => 'ResourceController@getResource']);
    $router->post('', ['uses' => 'ResourceController@create']);
    $router->put('{id}', ['uses' => 'ResourceController@update']);
    $router->delete('{id}', ['uses' => 'ResourceController@delete']);
});

$router->group(['prefix' => 'events'], function () use ($router) {
    $router->get('', ['uses' => 'EventController@getAllEvents']);
    $router->get('{id}', ['uses' => 'EventController@getEvent']);
    $router->get('{start}/{end}', ['uses' => 'EventController@getAllEventsInPeriod']);
    $router->post('', ['uses' => 'EventController@create']);
    $router->put('{id}', ['uses' => 'EventController@update']);
    $router->delete('{id}', ['uses' => 'EventController@delete']);
});
