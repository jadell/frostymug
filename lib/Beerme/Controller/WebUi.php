<?php
namespace Beerme\Controller;

use Silex\Application,
    Silex\Provider\SessionServiceProvider as Session,
	Symfony\Component\HttpFoundation\Request,
    Beerme\UserStore,
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
		$app->register(new Session());
		$app['userStore'] = $app->share(function($app) {
			return new UserStore($app['neo4j']);
		});

		$app->get('/', function () use ($app) {
			return WebUi::render($app['templateDir'].'/main.php', array(
				'user' => $app['session']->get('user'),
			));
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
					return $app->redirect('/');
				} else if (!$openId->validate()) {
					die('Invalid login');
				}

				$attributes = $openId->getAttributes();
				if (empty($attributes['contact/email'])) {
					die('Unable to determine email address');
				}

				$userStore = $app['userStore'];
				$user = $userStore->createUser($attributes['contact/email']);
				if ($user) {
					$app['session']->start();
					$app['session']->set('user', $user->toApi());
					return $app->redirect('/');
				}

			} catch (ErrorException $e) {
				die($e->getMessage());
			}
		});

		$app->get('/logout', function () use ($app) {
			$app['session']->set('user', null);
			$app['session']->invalidate();
			$app['session']->clear();
			return $app->redirect('/');
		});
	}

	/**
	 * Render the given template
	 *
	 * @param string $templateFile
	 * @param array $vars
	 * @return string
	 */
	public function render($templateFile, $vars=array())
	{
		extract($vars);
		ob_start();
		require($templateFile);
		return ob_get_clean();
	}
}