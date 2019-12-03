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
    });

});
