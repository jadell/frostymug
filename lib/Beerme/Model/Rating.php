<?php
namespace Beerme\Model;

use Beerme\Model\Beer,
	Beerme\Model\User;

interface Rating
{
	/**
	 * Return the rating and estimated rating
	 *
	 * @param Beer $beer
	 * @param User $user
	 * @return array ('rated'=>integer , 'estimated'=>integer)
	 */
	public function getRating(Beer $beer, User $user=null);
}