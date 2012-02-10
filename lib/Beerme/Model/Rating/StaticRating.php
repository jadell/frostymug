<?php
namespace Beerme\Model\Rating;

use Beerme\Model\Rating,
    Beerme\Model\User,
    Beerme\Model\Beer;

class StaticRating implements Rating
{
	protected $rating = array(
		'rated' => null,
		'estimated' => null,
	);

	/**
	 * Create the rating
	 *
	 * @param integer $rated
	 * @param integer $estimated
	 */
	public function __construct($rated=null, $estimated=null)
	{
		$this->rating = array(
			'rated' => $rated,
			'estimated' => $estimated,
		);
	}

	/**
	 * Return the rating and estimated rating
	 *
	 * @param Beer $beer
	 * @param User $user
	 * @return array ('rated'=>integer , 'estimated'=>integer)
	 */
	public function getRating(Beer $beer, User $user=null)
	{
		return $this->rating;
	}
}