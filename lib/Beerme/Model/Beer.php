<?php
namespace Beerme\Model;

use Everyman\Neo4j\Node,
    Beerme\BeerStore,
    Beerme\Model\Brewery;

class Beer
{
	protected $node;
	protected $beerStore;
	protected $brewery = false;

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
	 * @return array
	 */
	public function toApi()
	{
		$data = array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
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
}