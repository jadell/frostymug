<?php
namespace Beerme\Controller;

use Silex\Application;

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
	}
}