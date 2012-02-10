<?php
namespace Beerme\Model;

use Everyman\Neo4j\Node,
    Beerme\BeerStore,
    Beerme\Model\Rating,
    Beerme\Model\Brewery,
    Beerme\Model\User;

class Beer
{
	protected $node;
	protected $beerStore;
	protected $rating;
	protected $brewery = false;

	/**
	 * Create the beer
	 *
	 * @param Node $node
	 * @param BeerStore $beerStore
	 * @param Rating $rating
	 */
	public function __construct(Node $node, BeerStore $beerStore, Rating $rating)
	{
		$this->node = $node;
		$this->beerStore = $beerStore;
		$this->rating = $rating;
	}

	/**
	 * Retrieve the fields that the API expects
	 *
	 * @param User $user to retrieve ratings
	 * @return array
	 */
	public function toApi(User $user=null)
	{
		return array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'rating' => $this->rating->getRating($this, $user),
			'brewery' => $this->getBrewery()->toApi(),
		);
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
}