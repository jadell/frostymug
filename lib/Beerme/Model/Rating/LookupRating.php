<?php
namespace Beerme\Model\Rating;

use Beerme\Model\Rating,
    Beerme\Model\User,
    Beerme\Model\Beer,
    Beerme\BeerStore;

class LookupRating implements Rating
{
	protected $rating = array(
		'rated' => null,
		'estimated' => null,
	);

	protected $beerStore;

	/**
	 * Create the rating
	 *
	 * @param BeerStore $beerStore
	 */
	public function __construct(BeerStore $beerStore)
	{
		$this->beerStore = $beerStore;
	}

	/**
	 * Return the rating and estimated rating
	 *
	 * Attempts to get the actual rating.
	 * Only looks up the estimated rating if no
	 * actual rating is available
	 *
	 * @param Beer $beer
	 * @param User $user
	 * @return array ('rated'=>integer , 'estimated'=>integer)
	 */
	public function getRating(Beer $beer, User $user=null)
	{
		$this->rating['rated'] = $this->beerStore->getRating($beer, $user);
		if ($this->rating['rated'] === null) {
			$this->rating['estimated'] = $this->beerStore->getEstimatedRating($beer, $user);;
		} else {
			$this->rating['estimated'] = null;
		}
		return $this->rating;
	}
}