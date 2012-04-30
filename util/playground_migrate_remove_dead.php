<?php
require("phar://lib/neo4jphp.phar");

$client = new Everyman\Neo4j\Client('1cd95fc82.hosted.neo4j.org', 7048);
$client->getTransport()->setAuth('bc2cc378b', '9213d2e1c');

// Delete unrated beers
$cypher =
	'START ref=node(3)
	MATCH ref<-[:BEER]-b<-[r?:RATED]-u
	WHERE r IS NULL
	RETURN b LIMIT 100';
do {
	$query = new Everyman\Neo4j\Cypher\Query($client, $cypher);
	$results = $query->getResultSet();

	if (count($results) < 1) {
		continue;
	}

	$client->startBatch();
	foreach ($results as $row) {
		$beer = $row[0];
		$id = $beer->getId();
		$name = $beer->getProperty('name');
		$relationships = $beer->getRelationships();

		echo "DELETE $id $name\n";
		foreach ($relationships as $rel) {
			$rel->delete();
		}
		$beer->delete();
	}
	$client->commitBatch();

} while (count($results) > 0);

// Delete unused breweries
$cypher =
	'START ref=node(1)
	MATCH ref<-[:BREWERY]-y-[r?:BREWS]->b
	WHERE r IS NULL
	RETURN y LIMIT 100';
do {
	$query = new Everyman\Neo4j\Cypher\Query($client, $cypher);
	$results = $query->getResultSet();

	if (count($results) < 1) {
		continue;
	}

	$client->startBatch();
	foreach ($results as $row) {
		$brewery = $row[0];
		$id = $brewery->getId();
		$name = $brewery->getProperty('name');
		$relationships = $brewery->getRelationships();

		echo "DELETE $id $name\n";
		foreach ($relationships as $rel) {
			$rel->delete();
		}
		$brewery->delete();
	}
	$client->commitBatch();

} while (count($results) > 0);
