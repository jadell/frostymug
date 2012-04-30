<?php
require("phar://lib/neo4jphp.phar");

$client = new Everyman\Neo4j\Client('1cd95fc82.hosted.neo4j.org', 7048);
$client->getTransport()->setAuth('bc2cc378b', '9213d2e1c');

$beerIndex = new Everyman\Neo4j\Index\NodeIndex($client, 'BEER');
$breweryIndex = new Everyman\Neo4j\Index\NodeIndex($client, 'BREWERIES');

// Beers
$cypher =
	'START ref=node(3)
	MATCH ref<-[:BEER]-b
	RETURN b SKIP {sk} LIMIT {li}';
$li = 20;
$sk = 0;
$found = 0;
do {
	$query = new Everyman\Neo4j\Cypher\Query($client, $cypher, array('sk'=>$sk, 'li'=>$li));
	$sk += $li;
	$results = $query->getResultSet();

	if (count($results) < 1) {
		continue;
	}

	$client->startBatch();
	foreach ($results as $row) {
		$beer = $row['b'];
		$found++;
		echo $beer->getProperty("id")."\n";

		$beerIndex->remove($beer);
		$beerIndex->add($beer, 'id', $beer->getProperty('id'));
	}
	$client->commitBatch();

} while (count($results) > 0);
echo "Updated $found beers\n";

// Breweries
$cypher =
	'START ref=node(1)
	MATCH ref<-[:BREWERY]-b
	RETURN b SKIP {sk} LIMIT {li}';
$li = 20;
$sk = 0;
$found = 0;
do {
	$query = new Everyman\Neo4j\Cypher\Query($client, $cypher, array('sk'=>$sk, 'li'=>$li));
	$sk += $li;
	$results = $query->getResultSet();

	if (count($results) < 1) {
		continue;
	}

	$client->startBatch();
	foreach ($results as $row) {
		$brewery = $row['b'];
		$found++;
		echo $brewery->getProperty("id")."\n";

		$breweryIndex->remove($brewery);
		$breweryIndex->add($brewery, 'id', $brewery->getProperty('id'));
	}
	$client->commitBatch();

} while (count($results) > 0);
echo "Updated $found breweries\n";
