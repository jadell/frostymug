<?php
namespace Beerme\Model;

use Silex\Application;

class Brewery
{

	protected $app;
	protected $id;
	protected $name;

	/**
	 * Construct a brewery
	 *
	 * @param Application
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Retrieve the fields that the API expects
	 *
	 * @return array
	 */
	public function toApi()
	{
		return array(
			'id' => $this->id,
			'name' => $this->name,
		);
	}

	/**
	 * Return the id
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return the name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set the id
	 *
	 * @param string $id
	 * @return Brewery
	 */
	public function setId($id)
	{
		$this->id = (string)$id;
		return $this;
	}

	/**
	 * Set the name
	 *
	 * @param string $name
	 * @return Brewery
	 */
	public function setName($name)
	{
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set properties on the brewery
	 *
	 * @param array $properties
	 * @return Brewery
	 */
	public function setProperties($properties)
	{
		if (isset($properties['name'])) {
			$this->setName($properties['name']);
		}

		return $this;
	}
}