<?php
namespace Beerme;

use Symfony\Component\HttpFoundation\Response;

class JsonResponse extends Response
{
	/**
	 * Build the response
	 *
	 * @param array $data
	 * @param integer $code
	 * @param array $headers
	 */
	public function __construct($data, $code=200, $headers=array())
	{
		if ($data !== null) {
			$data = json_encode($data);
		}

		parent::__construct($data, $code, array_merge(array(
			'Content-Type' => 'application/json',
		), $headers));
	}
}

