<?php
namespace Beerme;

use Silex\Application,
    Beerme\Model\User,
    Everyman\Neo4j\Client,
    Everyman\Neo4j\Index\NodeIndex,
    Everyman\Neo4j\Cypher\Query,
    Everyman\Neo4j\Node;

class UserStore
{
	protected $client;

	protected $userIndex;
	protected $userRef;

	/**
	 * Set up the store
	 *
	 * @param Client $client
	 */
	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	/**
	 * Create a new user
	 *
	 * @param string $email
	 * @return User
	 */
	public function createUser($email)
	{
		$existingUser = $this->getUserByEmail($email);
		if ($existingUser) {
			return $existingUser;
		}

		$client = $this->client;
		$userIndex = $this->getUserIndex();
		$ref = $this->getUsersReference();

		$client->startBatch();
		$userNode = $client->makeNode()
			->setProperties(array(
				'email' => $email,
			))
			->save()
			->relateTo($ref, 'USER')
				->save()
				->getStartNode();
		$userIndex->add($userNode, 'email', $userNode->getProperty('email'));
		$client->commitBatch();

		return new User($userNode);
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
			return null;
		}

		return = new User($userNode);
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
		if (!$this->userRef) {
			$client = $this->client;
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
			$this->userIndex = new NodeIndex($this->client, 'USERS');
			$this->userIndex->save();
		}
		return $this->userIndex;
	}
}