<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// API routes
$routes->group('api', function($routes){
	$routes->post('login', 'API\\Auth::login');
	$routes->post('refresh', 'API\\Auth::refresh');
	$routes->get('violations', 'API\\Violations::index');
	$routes->post('violations', 'API\\Violations::create');
	$routes->get('violations/(:num)', 'API\\Violations::show/$1');
	$routes->put('violations/(:num)', 'API\\Violations::update/$1');
	$routes->delete('violations/(:num)', 'API\\Violations::delete/$1');
	$routes->get('observations', 'API\\Observations::index');
	$routes->post('observations', 'API\\Observations::create');
	$routes->get('observations/(:num)', 'API\\Observations::show/$1');
	$routes->put('observations/(:num)', 'API\\Observations::update/$1');
	$routes->delete('observations/(:num)', 'API\\Observations::delete/$1');
	// Master data
	$routes->get('master/violation-types', 'API\\MasterData::violationTypes');
	$routes->get('master/vehicle-types', 'API\\MasterData::vehicleTypes');
	// Users master
	$routes->get('master/users', 'API\\Users::index');
	$routes->post('master/users', 'API\\Users::create');
	$routes->get('master/users/(:num)', 'API\\Users::show/$1');
	$routes->put('master/users/(:num)', 'API\\Users::update/$1');
	$routes->delete('master/users/(:num)', 'API\\Users::delete/$1');
	// System config
	$routes->get('master/configs', 'API\\Configs::index');
	// Reports and dashboard
	$routes->get('reports/violations-by-type', 'API\\Reports::violationsByType');
	$routes->get('reports/observations-by-vehicle', 'API\\Reports::observationsByVehicle');
	$routes->get('reports/daily-violations', 'API\\Reports::dailyViolations');
	$routes->get('dashboard/summary', 'API\\Dashboard::summary');
	$routes->post('logout', 'API\\Auth::logout');
});
