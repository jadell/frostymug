<?php
namespace Beerme\Model;

use Silex\Application;

class Beer
{
	protected $app;
	protected $id;
	protected $name;

	/**
	 * Construct a beer
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
	 * @return integer
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
	 * @param integer $id
	 * @return Beer
	 */
	public function setId($id)
	{
		$this->id = (integer)$id;
		return $this;
	}

	/**
	 * Set the name
	 *
	 * @param string $name
	 * @return Beer
	 */
	public function setName($name)
	{
		$this->name = (string)$name;
		return $this;
	}
}