<?php
namespace Beerme\Model;

use Everyman\Neo4j\Node,
    Beerme\BeerStore,
    Beerme\RatingStore,
    Beerme\Model\Brewery,
    Beerme\Model\User;

class Beer
{
	protected $node;
	protected $beerStore;
	protected $ratingStore;
	protected $brewery = false;

	protected $rater;
	protected $rating = false;

	/**
	 * Create the beer
	 *
	 * @param Node $node
	 * @param BeerStore $beerStore
	 * @param RatingStore $ratingStore
	 */
	public function __construct(Node $node, BeerStore $beerStore, RatingStore $ratingStore)
	{
		$this->node = $node;
		$this->beerStore = $beerStore;
		$this->ratingStore = $ratingStore;
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
		if ($brewery) {
			$data['brewery'] = $brewery->toApi();
		}

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
	 * @param User $user
	 * @return integer
	 */
	public function getRating(User $user=null)
	{
		if (!$user) {
			return null;
		} else {
			return $this->ratingStore->getRating($user, $this);
		}
	}
}