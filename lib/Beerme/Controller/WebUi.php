<?php
namespace Beerme\Controller;

use Silex\Application,
	Symfony\Component\HttpFoundation\Request,
	LightOpenID;

class WebUi
{
	/**
	 * Register this controller with the application
	 *
	 * @param Application
	 */
	public static function register(Application $app)
	{
		$app->get('/', function () use ($app) {
			return $app->redirect('/index.html');
		});

		$app->post('/login', function (Request $request) use ($app) {
			$identifier = $request->get('openid_identifier');
			try {
				$openId = new LightOpenID($_SERVER['HTTP_HOST']);
				$openId->identity = $identifier;
				$openId->required = array('contact/email');

				return $app->redirect($openId->authUrl());
			} catch (ErrorException $e) {
				die($e->getMessage());
			}
		});

		$app->get('/login', function () use ($app) {
			try {
				$openId = new LightOpenID($_SERVER['HTTP_HOST']);
				if ($openId->mode == 'cancel') {
					return $app->redirect('/index.html');
				}

				echo 'User '.($openId->validate() ? $openId->identity.' has ' : 'has not ').'logged in.';
				print_r($openId->getAttributes());

			} catch (ErrorException $e) {
				die($e->getMessage());
			}
		});
	}
}