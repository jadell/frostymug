<?php
namespace Beerme\Controller;

class WebUi
{
	protected $app;

	/**
	 * Bootstrap the Web front-end
	 *
	 * @param \Silex\Application
	 */
	public function __construct(\Silex\Application $app)
	{
		$this->app = $app;
		$this->app->get('/', array($this, 'indexAction'));
	}

	/**
	 * Index page
	 */
	public function indexAction()
	{
		return $this->app->redirect('/index.html');
	}
}