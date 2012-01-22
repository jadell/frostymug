<?php
use Everyman\Neo4j\Client;

require('phar://neo4jphp.phar');

$neo4jConnection = parse_url(getenv('NEO4J_REST_URL'));
$client = new Client($neo4jConnection['host'], $neo4jConnection['port']);
$client->getTransport()
	->useHttps($neo4jConnection['scheme'] == 'https')
	->setAuth($neo4jConnection['user'], $neo4jConnection['pass']);

try {
	$info = $client->getServerInfo();
	print_r($info['version']);
} catch (Exception $e) {
	echo "Something went horribly askew!\n";
}
