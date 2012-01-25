<?php
namespace Beerme\Controller;

use Silex\Application,
    Beerme\JsonResponse,
    Beerme\Model\Beer;

class BeerApi
{
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
		$this->app->get('/api/beer', array($this, 'indexAction'));
		$this->app->get('/api/beer/{beerId}', array($this, 'getBeerAction'));
		$this->app->get('/api/beer/search/{searchTerm}', array($this, 'searchAction'));
	}

	/**
	 * Index page
	 */
	public function indexAction()
	{
		return new JsonResponse(array(
			'search' => $this->app['baseUrl'].'/api/beer/search/<search-term>',
			'beer' => $this->app['baseUrl'].'/api/beer/<id>',
		));
	}

	/**
	 * Return a specific beer
	 *
	 * @param string $beerId
	 */
	public function getBeerAction($beerId)
	{
		$brewerydb = $this->app['brewerydb'];
		$results = $brewerydb->getBeer($beerId);
		$beerData = $results['data'];
		$beer = $this->app['beerfactory']->getBeer($beerData['id'], $beerData);
		return new JsonResponse($beer->toApi());
	}

	/**
	 * Search Brewery DB for beers
	 *
	 * @param string $searchTerm
	 */
	public function searchAction($searchTerm)
	{
		$brewerydb = $this->app['brewerydb'];
		$results = $brewerydb->search($searchTerm, 'beer');

		if (!isset($results['data'])) {
			$results['data'] = array();
		}

		$beers = array();
		foreach ($results['data'] as $beerData) {
			$beers[] = $this->app['beerfactory']->getBeer($beerData['id'], $beerData)->toApi();
		}

		return new JsonResponse($beers);
	}
}