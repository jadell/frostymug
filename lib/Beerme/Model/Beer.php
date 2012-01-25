<?php
namespace Beerme\Model;

use Silex\Application,
    Beerme\Model\Factory\BreweryFactory,
    Beerme\Model\Brewery;

class Beer
{

	protected $app;
	protected $id;
	protected $name;
	protected $description;
	protected $breweryId;
	protected $brewery;

	/**
	 * Construct a beer
	 *
	 * @param Application
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Retrieve the fields that the API expects
	 *
	 * @return array
	 */
	public function toApi()
	{
		$data = array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'description' => $this->getDescription(),
		);

		$brewery = $this->getBrewery();
		if ($brewery) {
			$data['brewery'] = $brewery->toApi();
		}

		return $data;
	}

	/**
	 * Return the brewery object
	 *
	 * @return Brewery
	 */
	public function getBrewery()
	{
		return $this->brewery;
	}

	/**
	 * Return the description
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Return the id
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Return the name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set the brewery
	 *
	 * @param Brewery $brewery
	 * @return Beer
	 */
	public function setBrewery(Brewery $brewery=null)
	{
		$this->brewery = $brewery;
		return $this;
	}

	/**
	 * Set the description
	 *
	 * @param string $description
	 * @return Beer
	 */
	public function setDescription($description)
	{
		$this->description = (string)$description;
		return $this;
	}

	/**
	 * Set the id
	 *
	 * @param string $id
	 * @return Beer
	 */
	public function setId($id)
	{
		$this->id = (string)$id;
		return $this;
	}

	/**
	 * Set the name
	 *
	 * @param string $name
	 * @return Beer
	 */
	public function setName($name)
	{
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set properties on the beer
	 *
	 * @param array $properties
	 * @return Beer
	 */
	public function setProperties($properties)
	{
		if (isset($properties['name'])) {
			$this->setName($properties['name']);
		}
		if (isset($properties['description'])) {
			$this->setDescription($properties['description']);
		}

		if (isset($properties['breweries']) && count($properties['breweries']) > 0) {
			$breweryProperties = $properties['breweries'][0];
			$this->setBrewery($this->app['breweryfactory']
			    ->getBrewery($breweryProperties['id'], $breweryProperties));
		}

		return $this;
	}
}