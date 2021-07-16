<?php

namespace App\Services\LinkedIn;

use LinkedIn\LinkedIn as LinkedInClient;

class LinkedIn extends LinkedInClient
{
	const API_BASE = 'https://api.linkedin.com/v2';
	const SCOPE_BASIC_PROFILE = 'r_liteprofile';
	const SCOPE_W_MEMBER_SOCIAL = 'w_member_social';

	/**
	 * Make an authenticated API request to the specified endpoint
	 * Headers are for additional headers to be sent along with the request.
	 * Curl options are additional curl options that may need to be set
	 *
	 * @param string $endpoint
	 * @param array $payload
	 * @param string $method
	 * @param array $headers
	 * @param array $curl_options
	 * @return array
	 */
	public function fetch($endpoint, array $payload = array(), $method = 'GET', array $headers = array(), array $curl_options = array())
	{
		$endpoint = self::API_BASE . '/' . trim($endpoint, '/\\') . '?oauth2_access_token=' . $this->getAccessToken();
		$headers[] = 'x-li-format: json';

		return $this->_makeRequest($endpoint, $payload, $method, $headers, $curl_options);
	}
}