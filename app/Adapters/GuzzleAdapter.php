<?php

namespace App\Adapters;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;

class GuzzleAdapter {

	public function __construct()
	{
		return new GuzzleClient(['defaults'=> ['verify' => FALSE]]);
	}

	public function createRequest($method, $url, $body)
	{
		return new Request($method, $url, [], $body);
	}

}