<?php
namespace Beerme\Model;

use Silex\Application;

class Beer
{
	protected $app;
	protected $id;

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
	 * Set the id
	 *
	 * @param integer $id
	 * @return Beer
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}
}