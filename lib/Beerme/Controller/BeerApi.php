<?php
namespace Beerme\Controller;

use Silex\Application,
    Beerme\JsonResponse,
    Beerme\BeerStore,
    Beerme\Model\Brewery,
    Beerme\Model\Beer;

class BeerApi
{
	protected $beerStore;

	/**
	 * Register this controller with the application
	 *
	 * @param Application
	 */
	public static function register(Application $app)
	{
		$app['beerStore'] = $app->share(function($app) {
			return new BeerStore($app['neo4j'], $app['brewerydb']);
		});

		$app['beerApi'] = $app->share(function($app) {
			return new BeerApi($app['beerStore']);
		});

		$app->get('/api/beer/{beerId}', function($beerId) use ($app) {
			$beer = $app['beerApi']->getBeer($beerId);
			if ($beer) {
				return new JsonResponse($beer->toApi());
			}
			return new JsonResponse((object)array(), 404);
		});

		// $app->get('/api/beer/search/{searchTerm}', function($searchTerm) use ($app) {
		// 	return new JsonResponse(array_map(function($beer) {
		// 		return $beer->toApi();
		// 	},
		// 	$app['beerapi']->searchBeers($searchTerm)));
		// });
	}

	/**
	 * Bootstrap the Beer endpoints
	 *
	 * @param Application
	 */
	public function __construct(BeerStore $beerStore)
	{
		$this->beerStore = $beerStore;
	}

	/**
	 * Return a specific beer
	 *
	 * @param string $beerId
	 * @return Beer
	 */
	public function getBeer($beerId)
	{
		return $this->beerStore->getBeer($beerId);
	}

	/**
	 * Search Brewery DB for beers
	 *
	 * @param string $searchTerm
	 * @return array of Beer
	 */
	// public function searchBeers($searchTerm)
	// {
	// 	$brewerydb = $this->app['brewerydb'];
	// 	$results = $brewerydb->search($searchTerm, 'beer');

	// 	if (!isset($results['data'])) {
	// 		$results['data'] = array();
	// 	}

	// 	$beers = array();
	// 	foreach ($results['data'] as $beerData) {
	// 		if (isset($beerData['breweries'][0])) {
	// 			$beerData['brewery'] = $this->getBrewery(
	// 				$beerData['breweries'][0]['id'],
	// 				$beerData['breweries'][0]
	// 			);
	// 		}
	// 		$beers[] = $this->getBeer($beerData['id'], $beerData);
	// 	}

	// 	return $beers;
	// }
}