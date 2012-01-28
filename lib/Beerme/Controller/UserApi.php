<?php
namespace Beerme\Controller;

use Silex\Application,
    Symfony\Component\HttpFoundation\Request,
    Beerme\JsonResponse,
    Beerme\Model\User,
    Everyman\Neo4j\Index\NodeIndex,
    Everyman\Neo4j\Cypher\Query,
    Everyman\Neo4j\Node;

class UserApi
{
	protected $userIndex;
	protected $userRef;
	protected $userNodes = array();

	/**
	 * Register this controller with the application
	 *
	 * @param Application
	 */
	public static function register(Application $app)
	{
		$app['userapi'] = $app->share(function($app) {
			return new UserApi($app);
		});

		$app->get('/api/user/logout', function() {
			return new JsonResponse(array());
		});

		$app->post('/api/user/login', function(Request $request) use ($app) {
			$email = $request->get('email');
			return new JsonResponse($app['userapi']->authenticate($email)->toApi());
		});
	}

	protected $app;

	/**
	 * Bootstrap the User endpoints
	 *
	 * @param Application
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Validate the given email address
	 *
	 * @param string $email
	 */
	public function authenticate($email)
	{
		return $this->getUserByEmail($email);
	}

	/**
	 * Retrieve a user by their email address
	 *
	 * @param string $email
	 * @return User
	 */
	public function getUserByEmail($email)
	{
		$userIndex = $this->getUserIndex();
		$userNode = $userIndex->findOne('email', $email);

		if (!$userNode) {
			$client = $this->app['neo4j'];
			$ref = $this->getUsersReference();

			$userNode = $client->makeNode()
				->setProperty('email', $email)
				->save();
			$userIndex->add($userNode, 'email', $userNode->getProperty('email'));
			$ref->relateTo($userNode, 'USER')->save();
		}

		$this->userNodes[$userNode->getId()] = $userNode;

		$user = new User($this->app);
		$user->setId($userNode->getId())
			->setProperties(array('email' => $email));
		return $user;
	}

	////////////////////////////////////////////////////////////////////////////////
	// PROTECTED //////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////

	/**
	 * Find the users reference node or create it if it doesn't exist
	 *
	 * @return Node
	 */
	protected function getUsersReference()
	{
		if ($this->userRef) {
			return $this->userRef;
		}

		$client = $this->app['neo4j'];
		$query = new Query($client, "START z=node(0) MATCH (z)-[:USERS]->(ref) RETURN ref");
		$results = $query->getResultSet();
		if (count($results) < 1) {
			$this->userRef = $client->getReferenceNode()
				->relateTo($client->makeNode()->save(), 'USERS')
				->save()
				->getEndNode();
		} else {
			$this->userRef = $results[0]['ref'];
		}

		return $this->userRef;
	}

	/**
	 * Get the Users index
	 *
	 * @return NodeIndex
	 */
	protected function getUserIndex()
	{
		if (!$this->userIndex) {
			$this->userIndex = new NodeIndex($this->app['neo4j'], 'USERS');
			$this->userIndex->save();
		}
		return $this->userIndex;
	}
}