<?php
namespace App\Services\QuickBookPayments;

use App\Services\QuickBookPayments\Clients\OAuth2Client;
use App\Models\QuickBook;
use App\Repositories\QBOAuth2AccessTokenRepository as AccessTokenRepo;
use App\Exceptions\QuickBookPayments\QuickBookOAuth2Exception;
use App\Services\QuickBookPayments\Objects\AccessToken;
use Exception;
use App\Services\QuickBookDesktop\Exceptions\QuickBookCompanyConnectionException;

class QuickBookServiceForSubscriber
{
	/**
	 * With the instantiation of the service object, two other classes will also get instantiated, also the scope will get set too.
	 */
	public function __construct()
	{
		$this->oauthClient = new OAuth2Client;
		$this->accessTokenRepo = new AccessTokenRepo;
		if(!getScopeId()) {
			setScopeId(\Auth::user()->company->id);
		}
	}
	/**
	 * @param $state String
	 */
	public function setState($state)
	{
		$this->company = $state->company;
	}
	/**
	 * This methods will create the access token from the AuthCode object.
	 * @param \App\Services\QuickBookPayments\Objects\AuthToken The auth token created from the callback url.
	 * @return \App\Services\QuickBookPayments\Actions\AccessToken The access token created from the Auth Code.
	 */
	public function createAccessToken($authCodeObject)
	{
		$accessTokenFromServer = $this->oauthClient->getAccessToken($authCodeObject);
		return $this->saveAccessToken($accessTokenFromServer);
	}
	/**
	 * This method will return the Authorisation Url
	 * @return String The authorisation URL which needs to be visited for connected to QuickBooks
	 */
	public function getAuthorisationUrl($withPaymentsScope = false)
	{
		return $this->oauthClient->authorizationUrl($withPaymentsScope);
	}
	/**
	 * This method will make a AuthToken object from the callback URL
	 * @param  String $callbackUrl The callback URL returned by the authentication server of QuickBooks
	 * @return \App\Services\QuickBookPayments\Objects\AuthToken Instance of the AuthToken object created from the callback URL
	 */
	public function getAuthTokenFromCallback($callbackUrl)
	{
		return $this->oauthClient->captureAuthToken($callbackUrl);
	}
	/**
	 * This method will refresh the access token by hitting the Auth API end-points
	 * @param  \App\Services\QuickBookPayments\Actions\AccessToken $accessToken The current access token (expired one) in the form of the Access Token object
	 * @return \App\Services\QuickBookPayments\Actions\AccessToken The refreshed Access Token from the QB Auth Server as an AccessToken instance.
	 */
	public function refreshAccessToken(AccessToken $accessToken)
	{
		return $this->oauthClient->refreshAccessToken($accessToken);
	}
	/**
	 * This method will save the supplied AccessToken
	 * @param App\Services\QuickBookPayments\Actions\AccessToken $accessToken AccessToken created/refreshed from the server
	 * @return AccessToken
	 */
	private function saveAccessToken(AccessToken $accessToken)
	{
		try {
			$quickBookModel = new QuickBook;
			$response = $quickBookModel->saveAccessToken($accessToken);

			return $response;
		} catch (QuickBookCompanyConnectionException $e) {
			$this->oauthClient->revokeAccessToken($accessToken->getAccessToken());

			throw $e;
		} catch (Exception $e) {
			$this->oauthClient->revokeAccessToken($accessToken->getAccessToken());
			throw $e;
		}
	}
	/**
	 * This method will check whether access token exists for the currrent company or not
	 * @param App\Services\QuickBookPayments\Actions\AccessToken $accessToken AccessToken created/refreshed from the server
	 * @return QbOAuth2AccessToken
	 */
	public function checkAccessToken()
	{
		$company = getScopeId();
		$accessToken = $this->accessTokenRepo->getAccessTokenByCompanyId($company);
		return $accessToken;
	}
	/**
	 * This method will check whether the refresh token has expired or not
	 * @param  AccessToken $accessToken Access Token object to be checked
	 * @return Boolean
	 */
	public function checkRefreshToken(AccessToken $accessToken)
	{
		return !$this->accessTokenRepo->isRefreshTokenExpired($accessToken);
	}
	/**
	 * This method will check whether the current company is connected with quickbooks or is the connection valid
	 * @param $raiseException Boolean Whether to raise an exception or not
	 * @return Boolean
	 */
	public function checkCurrentCompanyQuickbooksConnected($raiseException = false)
	{
		$accessToken = $this->checkAccessToken();
		if(!$accessToken) {
			if($raiseException) {
				throw new QuickBookOAuth2Exception("Access Token Not Saved/Created");
			}
			return FALSE;
		}
		$accessTokenObj = (new AccessToken)->makeFromEloquentModel($accessToken);
		$refreshTokenValid = $this->checkRefreshToken($accessTokenObj);
		if(!$refreshTokenValid) {
			if($raiseException) {
				throw new QuickBookOAuth2Exception("Not connected! Token Expired");
			}
			return FALSE;
		}
	}
}