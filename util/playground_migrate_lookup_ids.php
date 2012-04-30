<?php
require("phar://lib/neo4jphp.phar");
require("lib/Pintlabs/Service/Brewerydb/Exception.php");
require("lib/Pintlabs/Service/Brewerydb.php");

$apiKey = '7ca9acb2ee8698fb1ccd44137de75b49';
$breweryDb = new Pintlabs_Service_Brewerydb($apiKey);
$client = new Everyman\Neo4j\Client('1cd95fc82.hosted.neo4j.org', 7048);
$client->getTransport()->setAuth('bc2cc378b', '9213d2e1c');

$beerProperties = array(
	'name' => null,
	'id' => null,
	'description' => null,
	'icon' => null,
	'breweries' => array(),
);
$breweryProperties = array(
	'name' => null,
	'id' => null,
	'description' => null,
	'icon' => null,
);

// List remaining
$beers = array();
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

	foreach ($results as $row) {
		$beers[] = $row['b'];
		$found++;
	}

} while (count($results) > 0);

// Lookup potential matches
$translations = array();
foreach ($beers as $beer) {
	$brewery = $beer->getFirstRelationship('BREWS')->getStartNode();

	$beerId = $beer->getId();
	$beerName = $beer->getProperty('name');
	$breweryId = $brewery->getId();
	$breweryName = $brewery->getProperty('name');
	$key = join('|', array($beerId,$beerName,$breweryId,$breweryName));

	$beerMatches = array();

	$results = $breweryDb->search($beerName, 'beer');
	if (isset($results['data'])) {
		$beerResults = $results['data'];
		foreach ($beerResults as $beerResult) {
			$beerMatch = $beerProperties;
			$beerMatch['name'] = $beerResult['name'];
			$beerMatch['id'] = $beerResult['id'];
			$beerMatch['description'] = isset($beerResult['description']) ? $beerResult['description']: null;
			$beerMatch['icon'] = isset($beerResult['labels']['icon']) ? $beerResult['labels']['icon']: null;

			$breweryResults = $beerResult['breweries'];
			foreach ($breweryResults as $breweryResult) {
				$breweryMatch = $breweryProperties;
				$breweryMatch['name'] = $breweryResult['name'];
				$breweryMatch['id'] = $breweryResult['id'];
				$breweryMatch['description'] = isset($breweryResult['description']) ? $breweryResult['description']: null;
				$breweryMatch['icon'] = isset($breweryResult['images']['icon']) ? $breweryResult['images']['icon']: null;
				$beerMatch['breweries'][] = $breweryMatch;
			}

			$beerMatches[] = $beerMatch;
		}
	}

	$translations[$key] = $beerMatches;
}

echo json_encode($translations)."\n";
