<?php
namespace Beerme\Model;

use Everyman\Neo4j\Node;

class User
{
	protected $node;

	/**
	 * Create the user
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
		$data = array(
			'email' => $this->getEmail(),
		);
		return $data;
	}

	/**
	 * Return the email
	 *
	 * @return string
	 */
	public function getEmail()
	{
		return $this->node->getProperty('email');
	}
}