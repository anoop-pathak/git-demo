<?php

namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\HoverClient;
use Carbon\Carbon;
use App\Models\HoverJob;
use Crypt;

class HoverRepository extends ScopedRepository
{
	/**
     * The base eloquent ActivityLog
     * @var Eloquent
     */
	protected $scope;
	protected $model;
 	function __construct(Context $scope, HoverClient $model)
	{
		$this->scope = $scope;
		$this->model = $model;
	}
  	/**
 	* save hover client data
 	*
 	* @param  $data, $userId, $companyID
 	* @return hover client record
 	*/
	public function saveHoverClient($ownerId, $ownerType, $userId, $companyId, $data = array())
	{
		try{
			$hover = HoverClient::firstOrNew([
				'owner_id'		=> $data['owner_id'],
				'owner_type'	=> $data['owner_type'],
				'company_id' 	=> $companyId,
				'created_by' 	=> $userId,
			]);
 			$tokenExpiryDateTime = Carbon::createFromTimestamp($data['created_at'])->addseconds($data['expires_in']);
 			$hover->access_token     = $data['access_token'];
			$hover->expiry_date_time = $tokenExpiryDateTime;
			$hover->refresh_token    = $data['refresh_token'];
			$hover->webhook_id       = $data['webhook_id'];
			$hover->save();
 			return $hover;
		}catch(Exception $e){
 			throw $e;
		}
	}
 	public function updateAccessToken($hoverClient, $accessToken, $refreshToken, $createdAt, $expireIn)
	{
		try{
			$tokenExpiryDateTime = Carbon::createFromTimestamp($createdAt)->addseconds($expireIn);
			$hoverClient->access_token     = $accessToken;
			$hoverClient->expiry_date_time = $tokenExpiryDateTime;
			$hoverClient->refresh_token    = $refreshToken;
			$hoverClient->save();
 			return $hoverClient;
		}catch(Exception $e){
 			throw $e;
		}
	}
 	/**
	* get access token 
	*
	* @return A hover client record
	*/
	public function getHoverClient()
	{
		return $this->make()->first();
	}
 	/**
	* get hover client by webhook id
	*/
	public function getByWebhookId($webhookId)
	{
		return $this->model->whereWebhookId($webhookId)->first();
	}
} 