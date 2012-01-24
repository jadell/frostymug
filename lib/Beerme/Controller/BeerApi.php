<?php
namespace Beerme\Controller;

use Silex\Application,
    Beerme\JsonResponse,
    Beerme\Model\Beer;

class BeerApi
{
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
	 * @param integer $beerId
	 */
	public function getBeerAction($beerId)
	{
		$brewerydb = $this->app['brewerydb'];
		$results = $brewerydb->getBeer($beerId);
		$beerData = $results['beers']['beer'];
		$beer = $this->hydrateBeer($beerData);
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
		if (isset($results['results']['result']['id'])) {
			$results['results']['result'] = array($results['results']['result']);
		}

		$beers = array();
		foreach ($results['results']['result'] as $beerData) {
			$beers[] = $this->hydrateBeer($beerData)->toApi();
		}

		return new JsonResponse($beers);
	}

	/**
	 * Turn an array of beer data into a beer object
	 *
	 * @param array $beerData
	 * @return Beer
	 */
	public function hydrateBeer($beerData)
	{
		$beer = new Beer($this->app);
		return $beer->setId($beerData['id'])
		            ->setName($beerData['name']);
	}
}