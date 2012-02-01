<?php
namespace Beerme;

use Silex\Application,
    Beerme\Model\Beer,
    Beerme\Model\Brewery,
    Everyman\Neo4j\Client,
    Everyman\Neo4j\Index\NodeIndex,
    Everyman\Neo4j\Cypher\Query,
    Everyman\Neo4j\Node,
    Pintlabs_Service_Brewerydb as BreweryDb;

class BeerStore
{
	protected $breweryDb;
	protected $neo4j;

	protected $breweryIndex;
	protected $breweryRef;

	protected $beerIndex;
	protected $beerRef;

	/**
	 * Set up the store
	 *
	 * @param Client $neo4j
	 * @param BreweryDb $breweryDb
	 */
	public function __construct(Client $neo4j, BreweryDb $breweryDb)
	{
		$this->neo4j = $neo4j;
		$this->breweryDb = $breweryDb;
	}

	/**
	 * Retrieve a brewery by its id
	 *
	 * @param string $breweryId
	 * @return Brewery
	 */
	public function getBrewery($breweryId)
	{
		$brewery = $this->findBreweryInGraph($breweryId);
		if ($brewery) {
			return $brewery;
		}

		$brewery = $this->findBreweryInBreweryDb($breweryId);
		if ($brewery) {
			return $brewery;
		}
		return null;
	}

	/**
	 * Retrieve a brewery for the given beer
	 *
	 * @param Beer $beer
	 * @return Brewery
	 */
	public function getBreweryForBeer(Beer $beer)
	{
		$breweryRel = $beer->getNode()->getFirstRelationship('BREWS');
		if ($breweryRel) {
			return new Brewery($breweryRel->getStartNode());
		} else {
			return null;
		}
	}


	/**
	 * Retrieve a beer by its id
	 *
	 * @param string $beerId
	 * @return Beer
	 */
	public function getBeer($beerId)
	{
		$beer = $this->findBeerInGraph($beerId);
		if ($beer) {
			return $beer;
		}

		$beer = $this->findBeerInBreweryDb($beerId);
		if ($beer) {
			return $beer;
		}
		return null;
	}

	/**
	 * Search Brewery DB for beers
	 *
	 * @param string $searchTerm
	 * @return array of Beer
	 */
	public function searchBeers($searchTerm)
	{
		$results = $this->breweryDb->search($searchTerm, 'beer');

		if (!isset($results['data'])) {
			$results['data'] = array();
		}

		$beers = array();
		foreach ($results['data'] as $beerData) {
			$beers[] = $this->getBeer($beerData['id']);
		}

		return $beers;
	}

	////////////////////////////////////////////////////////////////////////////////
	// PROTECTED //////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////

	/**
	 * Look up beer in BreweryDb
	 *
	 * @param string $id
	 * @return Beer
	 */
	protected function findBeerInBreweryDb($id)
	{
		$beerResults = $this->breweryDb->getBeer($id);
		if (!isset($beerResults['data'])) {
			return null;
		}
		$beerData = $beerResults['data'];

		$breweryResults = $this->breweryDb->getBreweriesForBeer($id);
		if (!isset($breweryResults['data'][0])) {
			return null;
		} else {
			$brewery = $this->getBrewery($breweryResults['data'][0]['id']);
		}

		$properties = array(
			'id' => $beerData['id'],
			'name' => $beerData['name'],
			'description' => isset($beerData['description']) ? $beerData['description'] : null,
		);

		$client = $this->neo4j;
		$index = $this->getBeerIndex();
		$ref = $this->getBeerReference();

		$client->startBatch();
		$node = $client->makeNode()
			->setProperties($properties)
			->save()
			->relateTo($ref, 'BEER')
				->save()
				->getStartNode();
		$index->add($node, 'id', $node->getProperty('id'));
		$brewery->getNode()->relateTo($node, 'BREWS')->save();
		$client->commitBatch();

		return new Beer($node, $this);
	}

	/**
	 * Look up beer in Neo4j
	 *
	 * @param string $id
	 * @return Beer
	 */
	protected function findBeerInGraph($id)
	{
		$index = $this->getBeerIndex();
		$node = $index->findOne('id', $id);
		if ($node) {
			return new Beer($node, $this);
		}
		return null;
	}

	/**
	 * Look up brewery in BreweryDb
	 *
	 * @param string $id
	 * @return Brewery
	 */
	protected function findBreweryInBreweryDb($id)
	{
		$results = $this->breweryDb->getBrewery($id);
		if (!isset($results['data'])) {
			return null;
		}

		$properties = array(
			'id' => $results['data']['id'],
			'name' => $results['data']['name'],
			'icon' => isset($results['data']['images']['icon']) ? $results['data']['images']['icon'] : null,
		);

		$client = $this->neo4j;
		$index = $this->getBreweryIndex();
		$ref = $this->getBreweryReference();

		$client->startBatch();
		$node = $client->makeNode()
			->setProperties($properties)
			->save()
			->relateTo($ref, 'BREWERY')
				->save()
				->getStartNode();
		$index->add($node, 'id', $node->getProperty('id'));
		$client->commitBatch();

		return new Brewery($node);
	}

	/**
	 * Look up brewery in Neo4j
	 *
	 * @param string $id
	 * @return Brewery
	 */
	protected function findBreweryInGraph($id)
	{
		$index = $this->getBreweryIndex();
		$node = $index->findOne('id', $id);
		if ($node) {
			return new Brewery($node);
		}
		return null;
	}

	/**
	 * Get the Beer index
	 *
	 * @return NodeIndex
	 */
	protected function getBeerIndex()
	{
		if (!$this->beerIndex) {
			$this->beerIndex = new NodeIndex($this->neo4j, 'BEER');
			$this->beerIndex->save();
		}
		return $this->beerIndex;
	}

	/**
	 * Find the beer reference node or create it if it doesn't exist
	 *
	 * @return Node
	 */
	protected function getBeerReference()
	{
		if (!$this->beerRef) {
			$client = $this->neo4j;
			$query = new Query($client, "START z=node(0) MATCH (z)-[:BEERS]->(ref) RETURN ref");
			$results = $query->getResultSet();
			if (count($results) < 1) {
				$this->beerRef = $client->getReferenceNode()
					->relateTo($client->makeNode()->save(), 'BEERS')
					->save()
					->getEndNode();
			} else {
				$this->beerRef = $results[0]['ref'];
			}
		}

		return $this->beerRef;
	}

	/**
	 * Get the Breweries index
	 *
	 * @return NodeIndex
	 */
	protected function getBreweryIndex()
	{
		if (!$this->breweryIndex) {
			$this->breweryIndex = new NodeIndex($this->neo4j, 'BREWERIES');
			$this->breweryIndex->save();
		}
		return $this->breweryIndex;
	}

	/**
	 * Find the brewery reference node or create it if it doesn't exist
	 *
	 * @return Node
	 */
	protected function getBreweryReference()
	{
		if (!$this->breweryRef) {
			$client = $this->neo4j;
			$query = new Query($client, "START z=node(0) MATCH (z)-[:BREWERIES]->(ref) RETURN ref");
			$results = $query->getResultSet();
			if (count($results) < 1) {
				$this->breweryRef = $client->getReferenceNode()
					->relateTo($client->makeNode()->save(), 'BREWERIES')
					->save()
					->getEndNode();
			} else {
				$this->breweryRef = $results[0]['ref'];
			}
		}

		return $this->breweryRef;
	}
}