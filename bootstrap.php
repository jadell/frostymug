<?php
ini_set('display_errors', 'On');
ini_set('display_startup_errors', 'On');

define('APPLICATION_ROOT', __DIR__);
define('APPLICATION_LIB', APPLICATION_ROOT.'/lib');

require('phar://'.APPLICATION_LIB.'/neo4jphp.phar');
require('phar://'.APPLICATION_LIB.'/silex.phar');
spl_autoload_register(function ($className) {
	$classFile = $className;
	$classFile = str_replace('_',DIRECTORY_SEPARATOR,$classFile);
	$classFile = str_replace('\\',DIRECTORY_SEPARATOR,$classFile);
	$classPath = APPLICATION_LIB.'/'.$classFile.'.php';
	if (file_exists($classPath)) {
		require($classPath);
	}
});

$app = new Silex\Application();
$app['debug'] = true;

$app['baseUrl'] = $app->share(function ($app) {
	$request = $app['request'];
	return $request->getScheme().'://'.$request->getHttpHost().$request->getBasePath();
});

$app['neo4j'] = $app->share(function ($app) {
	$neo4jUrl = trim(getenv('NEO4J_REST_URL'));
	if (!$neo4jUrl) {
		$neo4jUrl = 'http://localhost:7474';
	}
	$neo4jConnection = parse_url($neo4jUrl);
	$neo4jConnection['user'] = isset($neo4jConnection['user']) ? $neo4jConnection['user'] : null;
	$neo4jConnection['pass'] = isset($neo4jConnection['pass']) ? $neo4jConnection['pass'] : null;

	$client = new Everyman\Neo4j\Client($neo4jConnection['host'], $neo4jConnection['port']);
	$client->getTransport()
		->useHttps($neo4jConnection['scheme'] == 'https')
		->setAuth($neo4jConnection['user'], $neo4jConnection['pass']);
	return $client;
});

$app['brewerydb'] = $app->share(function ($app) {
	return new Pintlabs_Service_Brewerydb(getenv('BREWERYDB_API_KEY'));
});

// Register controllers
$app['webui'] = new Beerme\Controller\WebUi($app);
$app['beeraip'] = new Beerme\Controller\BeerApi($app);
