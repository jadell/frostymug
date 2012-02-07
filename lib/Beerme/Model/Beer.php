<?php
namespace Beerme\Model;

use Everyman\Neo4j\Node,
    Beerme\BeerStore,
    Beerme\Model\Brewery,
    Beerme\Model\User;

class Beer
{
	protected $node;
	protected $beerStore;
	protected $brewery = false;

	protected $defaultRating = false;
	protected $defaultEstimated = false;

	/**
	 * Create the beer
	 *
	 * @param Node $node
	 * @param BeerStore $beerStore
	 */
	public function __construct(Node $node, BeerStore $beerStore)
	{
		$this->node = $node;
		$this->beerStore = $beerStore;
	}

	/**
	 * Retrieve the fields that the API expects
	 *
	 * @param User $user to retrieve ratings, or null for no rating
	 * @return array
	 */
	public function toApi(User $user=null)
	{
		$data = array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'rating' => $this->getRating($user),
		);

		$brewery = $this->getBrewery();
		$data['brewery'] = $brewery ? $brewery->toApi() : null;

		return $data;
	}

	/**
	 * Return the brewery object
	 *
	 * @return Brewery
	 */
	public function getBrewery()
	{
		if ($this->brewery === false) {
			$this->brewery = $this->beerStore->getBreweryForBeer($this);
		}

		return $this->brewery;
	}

	/**
	 * Return the description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->node->getProperty('description');
	}

	/**
	 * Return the id
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->node->getProperty('id');
	}

	/**
	 * Return the name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->node->getProperty('name');
	}

	/**
	 * Return the storage node
	 *
	 * @return Node
	 */
	public function getNode()
	{
		return $this->node;
	}

	/**
	 * Return the rating as given by the given User
	 *
	 * If no user is specified, use the default rating for this beer
	 *
	 * @param User $user
	 * @return integer
	 */
	public function getRating(User $user=null)
	{
		$rating = array(
			'rated' => null,
			'estimated' => null,
		);

		if ($user) {
			$rating['rated'] = $this->beerStore->getRating($user, $this);
			if ($rating['rated'] === null) {
				$rating['estimated'] = 5;
			}
		} else {
			$rating = array(
				'rated' => $this->defaultRating,
				'estimated' => $this->defaultEstimated,
			);
		}


		return $rating;
	}

	/**
	 * Set default ratings to use
	 *
	 * Used if looking up ratings without passing in a user, or if the
	 * given user has not rated this beer
	 *
	 * @param integer $defaultRating
	 * @param integer $defaultEstimated
	 * @return Beer
	 */
	public function setDefaultRatings($defaultRating=null, $defaultEstimated=null)
	{
		$this->defaultRating = $defaultRating;
		$this->defaultEstimated = $defaultEstimated;
	}
}