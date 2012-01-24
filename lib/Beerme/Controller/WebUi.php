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
		echo "Coming soon<br>";
		// echo $this->app['baseUrl']."<br>";
		// $client = $this->app['neo4j'];
		// try {
		// 	$info = $client->getServerInfo();
		// 	print_r($info['version']);
		// } catch (Exception $e) {
		// 	echo "Something went horribly askew!\n";
		// }
	}
}