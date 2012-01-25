<?php
namespace Beerme\Model\Factory;

use Silex\Application,
    Beerme\Model\Brewery;

class BreweryFactory
{
	protected $app;
	protected $breweries = array();

	/**
	 * Construct a brewery factory
	 *
	 * @param Application
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Get a brewery from the brewery cache or generate one and put it in the cache
	 *
	 * If properties are given, set them on the Brewery
	 *
	 * @param integer $id
	 * @param array   $properties
	 * @return Brewery
	 */
	public function getBrewery($id, $properties=array())
	{
		if (!isset($this->breweries[$id])) {
			$brewery = new Brewery($this->app);
			$this->breweries[$id] = $brewery->setId($id);
		}

		return $this->breweries[$id]->setProperties($properties);
	}
}