<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    $api->group(['prefix' => 'auth'], function(Router $api) {
        $api->post('login', 'App\\Api\\V1\\Controllers\\LoginController@login');
        $api->post('logout', 'App\\Api\\V1\\Controllers\\LogoutController@logout');
    });

    $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
        $api->get('events', 'App\\Api\\V1\\Controllers\\EventController@index');
        $api->post('events', 'App\\Api\\V1\\Controllers\\EventController@create');

        $api->get('vehicles/{keywords}', 'App\\Api\\V1\\Controllers\\VehicleController@index');
        $api->post('vehicles/{id}/find', 'App\\Api\\V1\\Controllers\\VehicleController@find');
        $api->post('vehicles', 'App\\Api\\V1\\Controllers\\VehicleController@create');
        $api->post('vehicles/{id}/update', 'App\\Api\\V1\\Controllers\\VehicleController@update');

        $api->get('transactions/{keywords}', 'App\\Api\\V1\\Controllers\\TransactionController@index');

        $api->get('customer-types', 'App\\Api\\V1\\Controllers\\CustomerTypeController@index');

        $api->get('fixed-rates/{keywords}', 'App\\Api\\V1\\Controllers\\FixedRateController@index');
    });

    // $api->get('sample', 'App\\Api\\V1\\Controllers\\EventController@index');
});
