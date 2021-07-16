<?php

namespace App\Models;

use App\Services\QuickBookPayments\Objects\AccessToken;

class QbOAuth2AccessToken extends BaseModel {
	protected $table = 'quickbooks';
	protected $fillable = ['access_token', 'access_token_secret', 'expires_in', 'refresh_token_expires_in', 'company_id', 'token_type', 'quickbook_id'];
	/**
	 * getRefreshTokenAttribute
	 * For this:
	 * `$modelObj->refresh_token` returning `$modelObj->access_token_secret`
	 */
	public function getRefreshTokenAttribute()
	{
		return $this->access_token_secret;
	}
	// private $mappings = [
	// 	'x_refresh_token_expires_in' => 'refresh_token_expires_in'
	// ];
	public static function createFromAccessTokenObject(AccessToken $accessToken)
	{
		$instance = new static;
		foreach ($accessToken->payload() as $key => $value) {
			$instance->{$key} = $value;
		}
		return $instance;
	}
	public static function updateAccessTokenObject(AccessToken $accessToken)
	{
		$instance = new static;
		foreach ($accessToken->payload() as $key => $value) {
			$instance->{$key} = $value;
		}
		return $instance;
	}
	public function setXRefreshTokenExpiresIn($value)
	{
		$this->refresh_token_expires_in = $value;
	}
	
}