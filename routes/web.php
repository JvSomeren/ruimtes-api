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

/**
 * Auth
 */
$router->group(['prefix' => 'auth'], function() use ($router) {
    $router->post('login', ['uses' => 'AuthController@login']);
    $router->post('validateCookies', ['uses' => 'AuthController@validateCookies']);
});

$router->group(['prefix' => 'auth', 'middleware' => 'auth:user'], function() use ($router) {
    $router->post('clearSessions', ['uses' => 'AuthController@clearSessions']);
    $router->post('isAdmin', ['uses' => 'AuthController@isAdmin']);
    $router->post('logout', ['uses' => 'AuthController@logout']);
});

$router->group(['prefix' => 'auth', 'middleware' => 'auth:admin'], function() use ($router) {
    $router->get('admins', ['uses' => 'AuthController@getAllAdmins']);
    $router->post('addAdmin', ['uses' => 'AuthController@addAdmin']);
    $router->put('{id}', ['uses' => 'AuthController@updateAdmin']);
    $router->delete('{id}', ['uses' => 'AuthController@deleteAdmin']);
});

/**
 * Resources
 */
$router->group(['prefix' => 'resources', 'middleware' => 'auth:user'], function () use ($router) {
    $router->get('', ['uses' => 'ResourceController@getAllResources']);
    $router->get('{id}', ['uses' => 'ResourceController@getResource']);
});

$router->group(['prefix' => 'resources', 'middleware' => 'auth:admin'], function () use ($router) {
    $router->post('', ['uses' => 'ResourceController@create']);
    $router->put('{id}', ['uses' => 'ResourceController@update']);
    $router->delete('{id}', ['uses' => 'ResourceController@delete']);
});

/**
 * Events
 */
$router->group(['prefix' => 'events', 'middleware' => 'auth:user'], function () use ($router) {
    $router->get('', ['uses' => 'EventController@getAllEvents']);
    $router->get('{id}', ['uses' => 'EventController@getEvent']);
    $router->get('{start}/{end}', ['uses' => 'EventController@getAllEventsInPeriod']);
});

$router->group(['prefix' => 'events', 'middleware' => 'auth:admin'], function () use ($router) {
    $router->post('', ['uses' => 'EventController@create']);
    $router->put('{id}', ['uses' => 'EventController@update']);
    $router->delete('{id}', ['uses' => 'EventController@delete']);
});
