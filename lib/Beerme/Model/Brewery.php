<?php
namespace Beerme\Model;

use Everyman\Neo4j\Node;

class Brewery
{
	protected $node;

	/**
	 * Create the brewery
	 *
	 * @param Node $node
	 */
	public function __construct(Node $node)
	{
		$this->node = $node;
	}

	/**
	 * Retrieve the fields that the API expects
	 *
	 * @return array
	 */
	public function toApi()
	{
		return array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'icon' => $this->getIconUrl(),
		);
	}

	/**
	 * Return the icon url
	 *
	 * @return string
	 */
	public function getIconUrl()
	{
		return $this->node->getProperty('icon');
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