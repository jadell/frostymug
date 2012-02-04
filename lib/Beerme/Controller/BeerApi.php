<?php
namespace Beerme\Controller;

use Silex\Application,
	Symfony\Component\HttpFoundation\Request,
    Beerme\JsonResponse,
    Beerme\BeerStore,
    Beerme\UserStore,
    Beerme\RatingStore,
    Beerme\Model\User,
    Beerme\Model\Brewery,
    Beerme\Model\Beer;

class BeerApi
{
	protected $beerStore;
	protected $ratingStore;

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

		$app['ratingStore'] = $app->share(function($app) {
			return new RatingStore($app['neo4j']);
		});

		$app['beerApi'] = $app->share(function($app) {
			return new BeerApi($app['beerStore'], $app['ratingStore']);
		});

		$app->get('/api/beer/{beerId}', function($beerId) use ($app) {
			$beer = $app['beerApi']->getBeer($beerId);
			if ($beer) {
				return new JsonResponse($beer->toApi());
			}
			return new JsonResponse((object)array(), 404);
		});

		$app->get('/api/beer/{beerId}/rating/{email}', function($beerId, $email) use ($app) {
			$beer = $app['beerStore']->getBeer($beerId);
			$user = $app['userStore']->getUserByEmail($email);
			$check = $app['session']->get('user');

			if ($beer && $user->getEmail() == $check['email']) {
				$rating = $app['beerApi']->getRating($user, $beer);
				return new JsonResponse(array(
					'rating' => $rating,
				));
			}
			return new JsonResponse((object)array(), 404);
		});

		$app->post('/api/beer/{beerId}/rating/{email}', function($beerId, $email, Request $request) use ($app) {
			$beer = $app['beerStore']->getBeer($beerId);
			$user = $app['userStore']->getUserByEmail($email);
			$check = $app['session']->get('user');
			$rating = $request->get('rating');

			if ($beer && $user->getEmail() == $check['email']) {
				$app['beerApi']->rateBeer($user, $beer, $rating);
				return new JsonResponse(null, 204);
			}
			return new JsonResponse((object)array(), 404);
		});

		$app->get('/api/beer/search/{searchTerm}', function($searchTerm) use ($app) {
			return new JsonResponse(array_map(function($beer) {
				return $beer->toApi();
			},
			$app['beerApi']->searchBeers($searchTerm)));
		});
	}

	/**
	 * Bootstrap the Beer endpoints
	 *
	 * @param BeerStore $beerStore
	 * @param RatingStore $ratingStore
	 */
	public function __construct(BeerStore $beerStore, RatingStore $ratingStore)
	{
		$this->beerStore = $beerStore;
		$this->ratingStore = $ratingStore;
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
	 * Retrieve the given user's rating of the given beer
	 *
	 * @param User $user
	 * @param Beer $beer
	 */
	public function getRating(User $user, Beer $beer)
	{
		return $this->ratingStore->getRating($user, $beer);
	}

	/**
	 * Rate a beer as the given user
	 *
	 * @param User $user
	 * @param Beer $beer
	 * @param integer $rating
	 */
	public function rateBeer(User $user, Beer $beer, $rating)
	{
		return $this->ratingStore->rateBeer($user, $beer, $rating);
	}

	/**
	 * Search Brewery DB for beers
	 *
	 * @param string $searchTerm
	 * @return array of Beer
	 */
	public function searchBeers($searchTerm)
	{
		return $this->beerStore->searchBeers($searchTerm);
	}
}