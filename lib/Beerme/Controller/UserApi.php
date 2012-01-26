<?php
namespace Beerme\Controller;

use Silex\Application,
    Symfony\Component\HttpFoundation\Request,
    Beerme\JsonResponse,
    Beerme\Model\User;

class UserApi
{
	protected $app;

	/**
	 * Bootstrap the User endpoints
	 *
	 * @param Application
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
		$this->app->get('/api/user/logout', array($this, 'logoutAction'));
		$this->app->post('/api/user/login', array($this, 'loginAction'));
	}

	/**
	 * Login
	 *
	 * @param Request $request
	 */
	public function loginAction(Request $request)
	{
		$email = $request->get('email');
		return new JsonResponse(array(
			'email' => $email,
		));
	}

	/**
	 * Logout
	 */
	public function logoutAction()
	{
		return new JsonResponse(array());
	}
}