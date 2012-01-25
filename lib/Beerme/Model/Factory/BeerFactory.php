<?php
namespace Beerme\Model\Factory;

use Silex\Application,
    Beerme\Model\Beer;

class BeerFactory
{
	protected $app;
	protected $beers = array();

	/**
	 * Construct a beer factory
	 *
	 * @param Application
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Get a beer from the beer cache or generate one and put it in the cache
	 *
	 * If properties are given, set them on the Beer
	 *
	 * @param integer $id
	 * @param array   $properties
	 * @return Beer
	 */
	public function getBeer($id, $properties=array())
	{
		if (!isset($this->beers[$id])) {
			$beer = new Beer($this->app);
			$this->beers[$id] = $beer->setId($id);
		}

		return $this->beers[$id]->setProperties($properties);
	}
}