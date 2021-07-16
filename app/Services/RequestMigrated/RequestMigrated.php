<?php

namespace App\Services\RequestMigrated;

use Illuminate\Http\Request as IlluminateRequest;

class RequestMigrated extends IlluminateRequest {
	
	/**
	 * This method will override the Laravel 5's only method.
	 * As the project is upgraded from Laravel v4 to Laravel v5 
	 *
	 * Originial Description: Get a subset of the items from the input data.
	 * @param  array  $keys
	 * @return array
	 */
	public function onlyLegacy($keys)
	{
		$keys = is_array($keys) ? $keys : func_get_args();

		$results = [];

		$input = $this->all();

		foreach ($keys as $key)
		{
			array_set($results, $key, array_get($input, $key));
		}

		return $results;
	}
}