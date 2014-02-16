<?php
namespace Beerme;

use Beerme\Model\User,
    Everyman\Neo4j\Client;

class UserStore
{
	protected $client;

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
		$userLabel = $this->getUserLabel();

		$userNode = $client->makeNode()
			->setProperties(array(
				'email' => $email,
			))
			->save();
		$userNode->addLabels(array($userLabel));
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
		if (!$email) {
			return null;
		}

		$userLabel = $this->getUserLabel();
		$users = $userLabel->getNodes('email', $email);
		if (!count($users)) {
			return null;
		}
		return new User($users[0]);
	}

	////////////////////////////////////////////////////////////////////////////////
	// PROTECTED //////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////////

	/**
	 * Get the Users label
	 *
	 * @return Label
	 */
	protected function getUserLabel()
	{
		return $this->client->makeLabel('User');
	}
}