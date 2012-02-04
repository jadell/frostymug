<?php
namespace Beerme;

use Silex\Application,
    Beerme\Model\Beer,
    Beerme\Model\User,
    Everyman\Neo4j\Client,
    Everyman\Neo4j\Relationship,
    Everyman\Neo4j\Cypher\Query;

class RatingStore
{
	protected $neo4j;

	/**
	 * Set up the store
	 *
	 * @param Client $neo4j
	 */
	public function __construct(Client $neo4j)
	{
		$this->neo4j = $neo4j;
	}

	/**
	 * Retrieve the given user's rating of the given beer
	 *
	 * @param User $user
	 * @param Beer $beer
	 */
	public function getRating(User $user, Beer $beer)
	{
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

		$ratingRel->setProperty('rating', $rating)->save();
	}

	////////////////////////////////////////////////////////////////////////////////
	// PROTECTED //////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////

	/**
	 * Find existing ratings between a beer and a user
	 *
	 * @param User $user
	 * @param Beer $beer
	 * @return Relationship
	 */
	protected function findRatingRelationships(User $user, Beer $beer)
	{
		$cypher = "START u=node({user}), b=node({beer}) MATCH u-[r:RATED]->b RETURN r";
		$query = new Query($this->neo4j, $cypher, array(
			'user' => $user->getNode()->getId(),
			'beer' => $beer->getNode()->getId(),
		));
		return $query->getResultSet();
	}
}