<?php
namespace Beerme\Controller;

use Silex\Application,
    Beerme\JsonResponse,
    Beerme\Model\Brewery,
    Beerme\Model\Beer;

class BeerApi
{
	/**
	 * Register this controller with the application
	 *
	 * @param Application
	 */
	public static function register(Application $app)
	{
		$app['beerapi'] = $app->share(function($app) {
			return new BeerApi($app);
		});

		$app->get('/api/beer/{beerId}', function($beerId) use ($app) {
			return new JsonResponse($app['beerapi']
				->findBeer($beerId)
				->toApi()
			);
		});

		$app->get('/api/beer/search/{searchTerm}', function($searchTerm) use ($app) {
			return new JsonResponse(array_map(function($beer) {
				return $beer->toApi();
			},
			$app['beerapi']->searchBeers($searchTerm)));
		});
	}

	protected $breweries = array();
	protected $beers = array();
	protected $app;

	/**
	 * Bootstrap the Beer endpoints
	 *
	 * @param Application
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Return a specific beer
	 *
	 * @param string $beerId
	 * @return Beer
	 */
	public function findBeer($beerId)
	{
		$brewerydb = $this->app['brewerydb'];
		$results = $brewerydb->getBeer($beerId);
		$beerData = $results['data'];
		$beer = $this->getBeer($beerData['id'], $beerData);
		return $beer;
	}

	/**
	 * Search Brewery DB for beers
	 *
	 * @param string $searchTerm
	 * @return array of Beer
	 */
	public function searchBeers($searchTerm)
	{
		$brewerydb = $this->app['brewerydb'];
		$results = $brewerydb->search($searchTerm, 'beer');

		if (!isset($results['data'])) {
			$results['data'] = array();
		}

		$beers = array();
		foreach ($results['data'] as $beerData) {
			$beerData['brewery'] = $this->getBrewery('XYZ123', array(
				'name' => 'Some Brewey Co.',
			));
			$beers[] = $this->getBeer($beerData['id'], $beerData);
		}

		return $beers;
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
	public function getBeer($id=null, $properties=array())
	{
		if (!$id) {
			$beer = new Beer($this->app);
		} else if (!isset($this->beers[$id])) {
			$beer = new Beer($this->app);
			$this->beers[$id] = $beer->setId($id);
		} else {
			$beer = $this->beers[$id];
		}

		return $beer->setProperties($properties);
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
	public function getBrewery($id=null, $properties=array())
	{
		if (!$id) {
			$brewery = new Brewery($this->app);
		} else if (!isset($this->breweries[$id])) {
			$brewery = new Brewery($this->app);
			$this->breweries[$id] = $brewery->setId($id);
		} else {
			$brewery = $this->breweries[$id];
		}

		return $brewery->setProperties($properties);
	}
}