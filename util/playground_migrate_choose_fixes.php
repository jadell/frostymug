<?php
require("phar://lib/neo4jphp.phar");
$client = new Everyman\Neo4j\Client('1cd95fc82.hosted.neo4j.org', 7048);
$client->getTransport()->setAuth('bc2cc378b', '9213d2e1c');
$beerIndex = new Everyman\Neo4j\Index\NodeIndex($client, 'BEER');
$breweryIndex = new Everyman\Neo4j\Index\NodeIndex($client, 'BREWERIES');

// Read in the suggestions
$file = isset($argv[1]) ? $argv[1] : null;
$lookups = false;
if ($file) {
	$lookups = json_decode(file_get_contents($file));
}
if (!$lookups) {
	die("Invalid file\n");
}

foreach ($lookups as $key => $replacements) {
	list($beerNodeId, $beerOriginalName, $breweryNodeId, $breweryOriginalName) = explode('|', $key);
	$compareBeerName = preg_replace('/\W/', '', $beerOriginalName);
	$compareBreweryName = preg_replace('/\W/', '', $breweryOriginalName);

	if (!count($replacements)) {
		continue;
	}

	$choices = array();
	foreach ($replacements as $replacement) {
		$beerName = preg_replace('/\W/', '', $replacement->name);
		foreach ($replacement->breweries as $brewery) {
			$breweryName = preg_replace('/\W/', '', $brewery->name);
			if ($beerName == $compareBeerName && $breweryName == $compareBreweryName) {
				$choices[] = array('beer' => $replacement, 'brewery' => $brewery);
			}
		}
	}

	if (!$choices) {
		continue;
	}

	echo "   $beerOriginalName -- $breweryOriginalName\n";
	foreach ($choices as $i => $choice) {
		$beerName = $choice['beer']->name;
		$breweryName = $choice['brewery']->name;
		echo "$i: $beerName -- $breweryName\n";
	}
	do {
		$choiceNum = readline("Choice [0]: ");
		if (!$choiceNum) {
			$choiceNum = 0;
		}
	} while ($choiceNum != 'x' && !isset($choices[$choiceNum]));

	if ($choiceNum === 'x') {
		echo "\n\n";
		continue;
	}
	$choice = $choices[$choiceNum];

	$beerInfo = $choice['beer'];
	$beerNode = $client->getNode($beerNodeId);
	$beerNode->setProperties(array(
		'id' => $beerInfo->id,
		'name' => $beerInfo->name,
		'description' => $beerInfo->description,
		'icon' => $beerInfo->icon,
	));

	$breweryInfo = $choice['brewery'];
	$breweryNode = $client->getNode($breweryNodeId);
	$breweryNode->setProperties(array(
		'id' => $breweryInfo->id,
		'name' => $breweryInfo->name,
		'description' => $breweryInfo->description,
		'icon' => $breweryInfo->icon,
	));

	$client->startBatch();
	$beerIndex->remove($beerNode);
	$beerIndex->add($beerNode, 'id', $beerNode->getProperty('id'));
	$beerNode->save();

	$breweryIndex->remove($breweryNode);
	$breweryIndex->add($breweryNode, 'id', $breweryNode->getProperty('id'));
	$breweryNode->save();

	$client->commitBatch();

	unset($lookups->$key);
	echo "\n\n";
}

echo json_encode($lookups)."\n";
