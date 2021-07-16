<?php
namespace App\Repositories;

use App\Models\QbOAuth2AccessToken;
use App\Services\QuickBookPayments\Objects\AccessToken as AccessTokenObject;

class QBOAuth2AccessTokenRepository
{
	protected $model;
	function __construct() {
		
		$this->model = QbOAuth2AccessToken::whereRaw(\DB::raw('1 = 1'));
	}
	public function getAccessTokenByCompanyId($companyId)
	{
		$token = $this->model->where('company_id', $companyId)->orderBy('id', 'DESC')->first();
		return $token;
	}
	public function deleteAccessTokenByCompanyId($companyId)
	{
		$token = $this->model->where('company_id', $companyId)->delete();
		return $token;
	}
	public function updateAccessToken(AccessTokenObject $accessToken)
	{
		$token = $this->model->where('access_token_secret', $accessToken->getRefreshToken())
		->update(
			['access_token' => $accessToken->getAccessToken()]
		);
	}
	public function isAccessTokenExpired($accessToken)
	{
		$token = $this->model->where('access_token', $accessToken->getAccessToken())->first();
		$expiring_time = strtotime($token->updated_at) + $token->expires_in;
		return ($expiring_time <= time());
	}
	public function isRefreshTokenExpired($refreshToken)
	{
		$token = $this->model->where('access_token_secret', $refreshToken->getRefreshToken())->first();
		$expiring_time = strtotime($token->created_at) + $token->refresh_token_expires_in;
		return ($expiring_time <= time());
	}
	public function isCompanyQBConnected($companyId)
	{
		$token = $this->model->where('company_id', $companyId)->get();
		if(!$token) {
			return FALSE;
		}
		if($this->isRefreshTokenExpired($token->refreshToken)) {
			return FALSE;
		}
		
		return TRUE;
	}
}