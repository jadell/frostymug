<?php
use Everyman\Neo4j\Client;

require('phar://neo4jphp.phar');

$client = new Client('692740c56.hosted.neo4j.org', 7048);
$client->getTransport()->setAuth('4f0d4c9c2', 'ccb7b7898');
print_r($client->getServerInfo());
