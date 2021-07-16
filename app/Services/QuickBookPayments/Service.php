<?php

namespace App\Services\QuickBookPayments;

use App\Services\QuickBookPayments\Clients\OAuth2Client;
use App\Services\QuickBookPayments\Clients\QBPaymentsClient;
use App\Services\FinancialDetails\FinancialPayment;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Repositories\QBOAuth2AccessTokenRepository as AccessTokenRepo;
use App\Models\QbOAuth2AccessToken As OAuthAccessToken;
use App\Exceptions\QuickBookPayments\QuickBookOAuth2Exception;
use App\Services\QuickBookPayments\Objects\AccessToken;
use App\Services\QuickBookPayments\Objects\CreditCard;
use App\Services\QuickBookPayments\Objects\Charge;

class Service
{
	public function __construct()
	{
		$this->oauthClient = new OAuth2Client;
		$this->accessTokenRepo = new AccessTokenRepo;
		$company = getScopeId();
		$accessToken = $this->accessTokenRepo->getAccessTokenByCompanyId($company);
		if(!$accessToken) {
			throw new QuickBookOAuth2Exception("Access Token Not Available", 1);
		}
		
		$accessTokenObj = new AccessToken;
		$accessTokenObj->setAccessToken($accessToken->access_token);
		$accessTokenObj->setExpiresIn($accessToken->expires_in);
		$accessTokenObj->setRefreshToken($accessToken->access_token_secret);
		$accessTokenObj->setXRefreshTokenExpiresIn($accessToken->refresh_token_expires_in);
		if(!$this->checkRefreshToken($accessTokenObj)) {
			throw new QuickBookOAuth2Exception("Refresh Token Expired, Ask you provider to enable QuickBook Payments", 1);
		}
		if(!$this->checkAccessToken($accessTokenObj)) {
			$refreshedToken = $this->refreshAccessToken($accessTokenObj);
			$this->accessTokenRepo->updateAccessToken($refreshedToken);
			$accessTokenObj = $refreshedToken;
		}
		
		$this->qbClient = new QBPaymentsClient($accessTokenObj);
	}
	public function saveAccessToken(AccessToken $accessToken)
	{
		$oAuth = OAuthAccessToken::createFromAccessTokenObject($accessToken);
		
		$oAuth->user_id = getScopeId();
		$oAuth->company_id = getScopeId();
		
		$oAuth->save();
	}
	public function refreshAccessToken(AccessToken $accessToken)
	{
		return $this->oauthClient->refreshAccessToken($accessToken);
	}
	public function checkAccessToken(AccessToken $accessToken)
	{
		return !$this->accessTokenRepo->isAccessTokenExpired($accessToken);
	}
	public function checkRefreshToken(AccessToken $accessToken)
	{
		return !$this->accessTokenRepo->isRefreshTokenExpired($accessToken);
	}
	public function makePayment($invoiceIds, $amount, $creditCardDetails)
	{
		$creditCard = new CreditCard($creditCardDetails);
		$chargeObject = new Charge;
		$chargeObject
			->setAmount($amount)
			->setCard($creditCard)
			->setCurrency()
			->setContext();
		return $this->qbClient->createCharge($chargeObject);
	}
}