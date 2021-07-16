<?php

namespace App\Services\QuickBookPayments\Actions;

use App\Services\QuickBookPayments\Objects\AccessToken as AccessTokenObject;
use App\Services\QuickBookPayments\Objects\AuthToken as AuthTokenObject;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

trait AccessToken
{
	private $accessTokenUrl = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer";
 	/**
	 * This method will refresh the Access Token by using the current access token available
	 * @param  AccessTokenObject $tokenObject the current available access token.
	 * @return AccessTokenObject New refreshed token
	 */
	public function refreshAccessToken(AccessTokenObject $tokenObject)
	{
		$tokenJson = $this->sendRequest(
			$this->accessTokenUrl,
			'post',
			[
				'grant_type' => 'refresh_token',
				'refresh_token' => $tokenObject->getRefreshToken()
			],
			[
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode($this->getClientId() . ":" . $this->getClientSecret())
			]
		);
		$accessToken = new AccessTokenObject;
		return $accessToken->makeFromJson($tokenJson);
	}
 	/**
	 * This method will use the client to get a access token by using the Auth Token
	 * @param  AuthTokenObject $authTokenObject The Auth token extracted from Callback URI
	 * @return AccessTokenObject The access token obtained from server and returned as an AccessTokenObject
	 */
	public function getAccessToken(AuthTokenObject $authTokenObject)
	{
		$authTokenJson = $this->sendRequest(
			$this->accessTokenUrl,
			'post',
			$authTokenObject->payload(),
			[
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode($this->getClientId() . ":" . $this->getClientSecret())
			]
		);
		$accessToken = new AccessTokenObject;
		return $accessToken->makeFromJson($authTokenJson)->setRealmId($authTokenObject->getRealmId())->setWithPaymentsScope($authTokenObject->getWhetherPaymentsConnectedFromState());
	}

	/**
	 * This method is revoking an access token with AccessToken or RefreshToken
	 * @param  String $accessTokenOrRefreshToken
	 * @return response
	 */
	public function revokeAccessToken($accessTokenOrRefreshToken)
	{
		$oauth2LoginHelper = new OAuth2LoginHelper($this->getClientId(), $this->getClientSecret());
		$response = $oauth2LoginHelper->revokeToken($accessTokenOrRefreshToken);

		return $response;
	}
}