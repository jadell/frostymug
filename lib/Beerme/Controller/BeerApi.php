<?php
namespace Beerme\Controller;

use Silex\Application,
	Symfony\Component\HttpFoundation\Request,
    Beerme\JsonResponse,
    Beerme\BeerStore,
    Beerme\UserStore,
    Beerme\RatingStore;

class BeerApi
{
	/**
	 * Register this controller with the application
	 *
	 * @param Application
	 */
	public static function register(Application $app)
	{
		$app['beerStore'] = $app->share(function($app) {
			return new BeerStore($app['neo4j'], $app['brewerydb'], $app['ratingStore']);
		});

		$app['ratingStore'] = $app->share(function($app) {
			return new RatingStore($app['neo4j']);
		});

		$app->get('/api/beer/{beerId}', function($beerId) use ($app) {
			$userData = $app['session']->get('user');
			$user = $app['userStore']->getUserByEmail($userData['email']);
			$beer = $app['beerStore']->getBeer($beerId);
			if ($beer) {
				return new JsonResponse($beer->toApi($user));
			}
			return new JsonResponse((object)array(), 404);
		});

		$app->get('/api/beer/{beerId}/rating/{email}', function($beerId, $email) use ($app) {
			$beer = $app['beerStore']->getBeer($beerId);
			$user = $app['userStore']->getUserByEmail($email);
			$check = $app['session']->get('user');

			if ($beer && $user->getEmail() == $check['email']) {
				$rating = $app['ratingStore']->getRating($user, $beer);
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
				$app['ratingStore']->rateBeer($user, $beer, $rating);
				return new JsonResponse(null, 204);
			}
			return new JsonResponse((object)array(), 404);
		});

		$app->get('/api/beer/search/{searchTerm}', function($searchTerm) use ($app) {
			$userData = $app['session']->get('user');
			$user = $app['userStore']->getUserByEmail($userData['email']);
			return new JsonResponse(array_map(function($beer) use ($user) {
				return $beer->toApi($user);
			},
			$app['beerStore']->searchBeers($searchTerm)));
		});
	}
}