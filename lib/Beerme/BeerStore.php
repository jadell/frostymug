<?php
namespace Beerme;

use Silex\Application,
    Beerme\Model\User,
    Beerme\Model\Beer,
    Beerme\Model\Brewery,
    Beerme\Model\Rating\StaticRating,
    Beerme\Model\Rating\LookupRating,
    Everyman\Neo4j\Client,
    Everyman\Neo4j\Index\NodeIndex,
    Everyman\Neo4j\Cypher\Query as Cypher,
    Everyman\Neo4j\Gremlin\Query as Gremlin,
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
	 * Retrieve list of recommended beers for the user
	 *
	 * @param User $user
	 * @return array of Beer
	 */
	public function getBeerRecommendationsForUser(User $user=null)
	{
		if (!$user) {
			return array();
		}

		$userNodeId = $user->getNode()->getId();
		if (!$userNodeId) {
			return array();
		}

		$gremlin = <<<GREMLIN
		m=[:].withDefault{[0,0]};
		r=[:].withDefault{[0,0]};
		user=g.v(userId);
		user.outE("RATED").sideEffect{w=it.getProperty('rating')}
		        .inV.inE("RATED").outV.except([user]).back(2)
		        .sideEffect{diff=Math.abs(it.getProperty('rating')-w)}
		        .outV.sideEffect{ me=m[it.id]; me[0]++; me[1]+=diff; }.iterate();
		m.findAll{it.value[1]/it.value[0] <= 2}.collect{g.v(it.key)}._()
		        .outE("RATED").sideEffect{rating=it.rating}.inV
		        .sideEffect{me=r[it.id]; me[0]++; me[1]+=rating; }.iterate();
		r.collectEntries{key, value -> [key , value[1]/value[0]]}
				.findAll{it.value > 5}
		        .sort{a,b -> b.value <=> a.value}[0..24]
		        .collect{key,value -> [g.v(key), value]}._()
GREMLIN;
		$query = new Gremlin($this->neo4j, $gremlin, array(
			'userId'=>$userNodeId,
		));
		$results = $query->getResultSet();

		$beers = array();
		foreach ($results as $row) {
			// Work around a nested array bug in parsing result sets
			$beerNode = $this->neo4j->getEntityMapper()->getEntityFor($row[0][0]);
			$beers[] = new Beer($beerNode, $this, new StaticRating(null, $row[0][1]));
		}
		return $beers;
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
	 * Retrieve the estimated rating for the given beer
	 *
	 * @param Beer $beer
	 * @param User $user
	 * @return integer
	 */
	public function getEstimatedRating(Beer $beer, User $user=null)
	{
		if (!$user) {
			return null;
		}

		$userNodeId = $user->getNode()->getId();
		$beerNodeId = $beer->getNode()->getId();
		if (!$userNodeId || !$beerNodeId) {
			return null;
		}

		$gremlin = <<<GREMLIN
		m=[:].withDefault{[0,0]};
		user=g.v(userId);
		beer=g.v(beerId);
		user.outE("RATED").sideEffect{w=it.getProperty('rating')}
		        .inV.inE("RATED").outV.except([user]).back(2)
		        .sideEffect{diff=Math.abs(it.getProperty('rating')-w)}
		        .outV.sideEffect{ me=m[it.id]; me[0]++; me[1]+=diff; }.iterate();
		m.findAll{it.value[1]/it.value[0] <= 2}.collect{g.v(it.key)}._()
		        .outE("RATED").inV.filter{it==beer}.back(2).dedup().rating.mean()
GREMLIN;
		$query = new Gremlin($this->neo4j, $gremlin, array(
			'userId'=>$userNodeId,
			'beerId'=>$beerNodeId
		));
		$results = $query->getResultSet();
		$rating = $results[0][0];
		if ($rating == "NaN") {
			$rating = null;
		}
		return $rating;
	}

	/**
	 * Retrieve all beers rated by the user
	 *
	 * Beers will have their default rating set to the
	 * $user's rating to avoid re-lookups
	 *
	 * @param User $user
	 * @return array of Beer
	 */
	public function getRatedBeersByUser(User $user)
	{
		$rated = $this->findRatingRelationships($user);
		$beers = array();
		foreach ($rated as $row) {
			$beers[] = new Beer($row['b'], $this, new StaticRating($row['r']->getProperty('rating')));
		}
		return $beers;
	}

	/**
	 * Retrieve the given user's rating of the given beer
	 *
	 * @param Beer $beer
	 * @param User $user
	 * @return integer
	 */
	public function getRating(Beer $beer, User $user=null)
	{
		if (!$user) {
			return null;
		}

		$existing = $this->findRatingRelationships($user, $beer);
		return count($existing) > 0 ? $existing[0]['r']->getProperty('rating') : null;
	}

	/**
	 * Rate a beer
	 *
	 * @param User $user
	 * @param Beer $beer
	 * @param integer $rating
	 */
	public function rateBeer(User $user, Beer $beer, $rating)
	{
		$rating = (integer)$rating;
		$rating = max(0, $rating);
		$rating = min(10, $rating);

		$existing = $this->findRatingRelationships($user, $beer);
		if (count($existing)>0) {
			$ratingRel = $existing[0]['r'];
		} else {
			$ratingRel = $user->getNode()->relateTo($beer->getNode(), 'RATED');
		}

		$ratingRel->setProperties(array(
			'rating' => $rating,
			'timestamp' => time(),
		))
		->save();

		return $rating;
	}

	/**
	 * Search Brewery DB for beers
	 *
	 * For autocomplete, pass the $nameOnly parameter as true,
	 * which will cause only an array of names to be returned.
	 * The array will be limited to the top 15 most relevant
	 * matches.
	 *
	 * Searching only the name will also prevent found beers
	 * from being loaded into the database.
	 *
	 * @param string $searchTerm
	 * @param boolean $nameOnly
	 * @return array of Beer (or string, if $nameOnly is true)
	 */
	public function searchBeers($searchTerm, $nameOnly=false)
	{
		$results = $this->breweryDb->search($searchTerm, 'beer');

		if (!isset($results['data'])) {
			$results['data'] = array();
		}

		$beers = array();
		foreach ($results['data'] as $beerData) {
			if ($nameOnly) {
				$beers[] = $beerData['name'];
			} else {
				$beer = $this->findBeerInGraph($beerData['id']);
				if (!$beer) {
					$beer = $this->createBeerFromBreweryDbRaw($beerData);
				}
				$beers[] = $beer;
			}
		}

		if ($nameOnly) {
			$beers = array_slice(array_values($beers), 0, 15);
		}

		return $beers;
	}

	////////////////////////////////////////////////////////////////////////////////
	// PROTECTED //////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////

	/**
	 * Create a beer from the BreweryDb raw data
	 *
	 * This is mainly used so we can create beers from search
	 * without having to make another 1 or more round-trips to
	 * BreweryDb
	 *
	 * @param array $beerData
	 * @return Beer
	 */
	protected function createBeerFromBreweryDbRaw($beerData)
	{
		$id = $beerData['id'];

		if (isset($beerData['breweries'][0])) {
			$breweryData = $beerData['breweries'][0];
			$breweryId = $breweryData['id'];
			$brewery = $this->findBreweryInGraph($breweryId);
			if (!$brewery) {
				$brewery = $this->createBreweryFromBreweryDbRaw($breweryData);
			}
		} else {
			$breweryResults = $this->breweryDb->getBreweriesForBeer($id);
			if (!isset($breweryResults['data'][0])) {
				return null;
			} else {
				$brewery = $this->getBrewery($breweryResults['data'][0]['id']);
			}
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

		return new Beer($node, $this, new LookupRating($this));
	}

	/**
	 * Create a brewery from the BreweryDb raw data
	 *
	 * This is mainly used so we can create breweries from search
	 * without having to make another 1 or more round-trips to
	 * BreweryDb
	 *
	 * @param array $breweryData
	 * @return Brewery
	 */
	protected function createBreweryFromBreweryDbRaw($breweryData)
	{
		$properties = array(
			'id' => $breweryData['id'],
			'name' => $breweryData['name'],
			'icon' => isset($breweryData['images']['icon']) ? $breweryData['images']['icon'] : null,
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

		return $this->createBeerFromBreweryDbRaw($beerData);
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
			return new Beer($node, $this, new LookupRating($this));
		}
		return null;
	}

	/**
	 * Look up brewery in BreweryDb
	 *
	 * @param string $id
	 * @param string $id
	 * @return Brewery
	 */
	protected function findBreweryInBreweryDb($id)
	{
		$results = $this->breweryDb->getBrewery($id);
		if (!isset($results['data'])) {
			return null;
		}

		return $this->createBreweryFromBreweryDbRaw($results['data']);
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
	 * Find existing ratings between a beer and a user
	 *
	 * If $beer is not given, all ratings for the user will be returned
	 *
	 * @param User $user
	 * @param Beer $beer
	 * @return Relationship
	 */
	protected function findRatingRelationships(User $user, Beer $beer=null)
	{
		$cypher = "START u=node({user})";
		$params = array('user' => $user->getNode()->getId());
		if ($beer) {
			$cypher .= ", b=node({beer})";
			$params['beer'] = $beer->getNode()->getId();
		}
		$cypher .= "MATCH u-[r:RATED]->b RETURN b, r ORDER BY b.name";
		$query = new Cypher($this->neo4j, $cypher, $params);
		return $query->getResultSet();
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
			$query = new Cypher($client, "START z=node(0) MATCH (z)-[:BEERS]->(ref) RETURN ref");
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
			$query = new Cypher($client, "START z=node(0) MATCH (z)-[:BREWERIES]->(ref) RETURN ref");
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