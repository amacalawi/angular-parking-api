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
        $api->get('vehicles/{id}/find', 'App\\Api\\V1\\Controllers\\VehicleController@find');
        $api->get('vehicles/{id}/filter', 'App\\Api\\V1\\Controllers\\VehicleController@filter');
        $api->post('vehicles', 'App\\Api\\V1\\Controllers\\VehicleController@create');
        $api->put('vehicles/{id}/update', 'App\\Api\\V1\\Controllers\\VehicleController@update');

        $api->get('transactions/{keywords}', 'App\\Api\\V1\\Controllers\\TransactionController@index');
        $api->post('transactions/{rfid}/checkin', 'App\\Api\\V1\\Controllers\\TransactionController@checkin');
        $api->post('transactions/{id}/checkout', 'App\\Api\\V1\\Controllers\\TransactionController@checkout');
        $api->post('transactions/generate', 'App\\Api\\V1\\Controllers\\TransactionController@generate');

        $api->get('customer-types/{keywords}', 'App\\Api\\V1\\Controllers\\CustomerTypeController@index');
        $api->get('customer-types/{id}/filter', 'App\\Api\\V1\\Controllers\\CustomerTypeController@filter');

        $api->get('fixed-rates/{keywords}', 'App\\Api\\V1\\Controllers\\FixedRateController@index');
        $api->get('fixed-rates/{id}/find', 'App\\Api\\V1\\Controllers\\FixedRateController@find');
        $api->post('fixed-rates', 'App\\Api\\V1\\Controllers\\FixedRateController@create');
        $api->put('fixed-rates/{id}/update', 'App\\Api\\V1\\Controllers\\FixedRateController@update');
        $api->put('fixed-rates/{id}/modify', 'App\\Api\\V1\\Controllers\\FixedRateController@modify');

        $api->post('load-credits/{id}/{amount}/create', 'App\\Api\\V1\\Controllers\\LoadCreditController@create');
        $api->get('load-credits/{id}', 'App\\Api\\V1\\Controllers\\LoadCreditController@index');

        $api->get('subscription-rates/{keywords}', 'App\\Api\\V1\\Controllers\\SubscriptionRateController@index');
        $api->get('subscription-rates/{id}/find', 'App\\Api\\V1\\Controllers\\SubscriptionRateController@find');
        $api->post('subscription-rates', 'App\\Api\\V1\\Controllers\\SubscriptionRateController@create');
        $api->put('subscription-rates/{id}/update', 'App\\Api\\V1\\Controllers\\SubscriptionRateController@update');
        $api->put('subscription-rates/{id}/modify', 'App\\Api\\V1\\Controllers\\SubscriptionRateController@modify');

        $api->get('customers/{keywords}', 'App\\Api\\V1\\Controllers\\CustomerController@index');
        $api->get('customers/{id}/find', 'App\\Api\\V1\\Controllers\\CustomerController@find');
        $api->post('customers', 'App\\Api\\V1\\Controllers\\CustomerController@create');
        $api->put('customers/{id}/update', 'App\\Api\\V1\\Controllers\\CustomerController@update');
        $api->put('customers/{id}/modify', 'App\\Api\\V1\\Controllers\\CustomerController@modify');

        $api->get('subscriptions/{id}/find', 'App\\Api\\V1\\Controllers\\SubscriptionController@find');
        $api->post('subscriptions/{id}/{total_amount}/create', 'App\\Api\\V1\\Controllers\\SubscriptionController@create');
        $api->put('subscriptions/{id}/{total_amount}/update', 'App\\Api\\V1\\Controllers\\SubscriptionController@update');
        $api->put('subscriptions/{id}/modify', 'App\\Api\\V1\\Controllers\\SubscriptionController@modify');
        $api->delete('subscriptions/{id}/delete', 'App\\Api\\V1\\Controllers\\SubscriptionController@delete');

        $api->get('users/{keywords}', 'App\\Api\\V1\\Controllers\\UserController@index');
        $api->get('users/{id}/find', 'App\\Api\\V1\\Controllers\\UserController@find');
        $api->post('users', 'App\\Api\\V1\\Controllers\\UserController@create');
        $api->put('users/{id}/update', 'App\\Api\\V1\\Controllers\\UserController@update');
        $api->put('users/{id}/modify', 'App\\Api\\V1\\Controllers\\UserController@modify');

        $api->get('roles/{keywords}', 'App\\Api\\V1\\Controllers\\RoleController@index');
        $api->get('roles/{id}/find', 'App\\Api\\V1\\Controllers\\RoleController@find');
        $api->post('roles', 'App\\Api\\V1\\Controllers\\RoleController@create');
        $api->put('roles/{id}/update', 'App\\Api\\V1\\Controllers\\RoleController@update');
        $api->put('roles/{id}/modify', 'App\\Api\\V1\\Controllers\\RoleController@modify');

        $api->get('dashboard/get-all-sales', 'App\\Api\\V1\\Controllers\\DashboardController@index');
    });
    
    $api->get('dashboard/download-sales', 'App\\Api\\V1\\Controllers\\DashboardController@download');
    $api->post('transactions/{rfid}/auto-checkin', 'App\\Api\\V1\\Controllers\\TransactionController@create');
});
