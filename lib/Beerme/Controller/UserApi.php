<?php
namespace Beerme\Controller;

use Silex\Application,
	Symfony\Component\HttpFoundation\Request,
    Beerme\JsonResponse,
    Beerme\Model\User,
    Beerme\UserStore;

class UserApi
{
	protected $userStore;

	/**
	 * Register this controller with the application
	 *
	 * @param Application
	 */
	public static function register(Application $app)
	{
		$app['userStore'] = $app->share(function($app) {
			return new UserStore($app['neo4j']);
		});

		$app['userApi'] = $app->share(function($app) {
			return new UserApi($app['userStore']);
		});

		$app->get('/api/user/logout', function() {
			return new JsonResponse(array());
		});

		$app->post('/api/user/login', function(Request $request) use ($app) {
			$email = $request->get('email');
			return new JsonResponse($app['userApi']->authenticate($email)->toApi());
		});

		$app->get('/api/user/{email}', function($email) use ($app) {
			$user = $app['userApi']->getUser($email);
			if ($user) {
				return new JsonResponse($user->toApi());
			}
			return new JsonResponse((object)array(), 404);
		});
	}

	/**
	 * Bootstrap the User endpoints
	 *
	 * @param UserStore $userStore
	 */
	public function __construct(UserStore $userStore)
	{
		$this->userStore = $userStore;
	}

	/**
	 * Validate the given email address
	 *
	 * For now, just create the user if they don't already exist
	 *
	 * @param string $email
	 */
	public function authenticate($email)
	{
		return $this->userStore->createUser($email);
	}

	/**
	 * Retrieve user info by email address
	 *
	 * @param string $email
	 */
	public function getUser($email)
	{
		return $this->userStore->getUserByEmail($email);
	}
}