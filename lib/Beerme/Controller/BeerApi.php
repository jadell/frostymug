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
	}

	/**
	 * Index page
	 */
	public function indexAction()
	{
		return new JsonResponse(array(
			'search' => $this->app['baseUrl'].'/api/beer?q=<name>',
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
		$beer = new Beer($this->app);
		$beer->setId($beerId);

		return new JsonResponse($beer->toApi());
	}
}