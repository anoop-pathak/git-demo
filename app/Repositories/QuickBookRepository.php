<?php

namespace App\Repositories;

use App\Models\QuickBook;
use App\Services\Contexts\Context;
use App\Models\QuickBookConnectionHistory;
use Illuminate\Support\Facades\Auth;

class QuickBookRepository extends ScopedRepository
{
    protected $model;

    /*
    ** This is time in seconds which will reduced from expiry time of tokens (Access & Refresh both) while checking expiry.
    ** to have those be refreshed earlier from their actual expiry; Taking Latency of Code, Network requests etc.
    */
    const LAPSE_FOR_ACCESS_TOKEN = 1800;
    const LAPSE_FOR_REFRESH_TOKEN = 86400;

    public function __construct(QuickBook $model, Context $scope)
    {
        $this->scope = $scope;
        $this->model = $model;
    }

    public function save($quickBookId = null, $accessToken, $accessTokenSecret, $expires_in = null, $refresh_token_expires_in = null, $token_type = null){
        $quickBook = Quickbook::firstOrNew((['company_id' => $this->scope->id()]));
        $quickBook->quickbook_id = $quickBookId;
        $quickBook->access_token = $accessToken;
        $quickBook->access_token_secret = $accessTokenSecret;
        $quickBook->expires_in = $expires_in;
        $quickBook->refresh_token_expires_in = $refresh_token_expires_in;
        $quickBook->token_type = $token_type;
        return $quickBook->save();
    }

    public function updateByRefreshToken($refreshToken, $accessToken, $refreshTokenExpiresIn, $newRefreshToken, $accessTokenExpiresIn = 3600)
    {
        return $this->updateByAccessTokenSecret($refreshToken, $accessToken, $refreshTokenExpiresIn, $newRefreshToken);
    }

    public function updateByAccessTokenSecret($accessTokenSecret, $accessToken, $refreshTokenExpiresIn, $newRefreshToken, $accessTokenExpiresIn = 3600)
    {
        $model = $this->model->where('access_token_secret', $accessTokenSecret)->first();
        $model->expires_in = $accessTokenExpiresIn;
        $model->access_token = $accessToken;
        $model->access_token_secret = $newRefreshToken;
        $model->refresh_token_expires_in = $refreshTokenExpiresIn;
        
        # this `clone` is done as this method might be called within a transaction and we need to return the object with the refreshed access token
        $token = clone $model;
        $model->save();
     
        return $token;
    }

    public function getByColumn($attribute, $value, $column = ['*'])
    {
        return $this->model->where($attribute, $value)
            ->select($column)
            ->first();
    }

    public function getCompany()
    {
        return $this->model->whereCompanyId($this->scope->id())->first();
    }

    public function getToken()
    {
        $quickBook = $this->model
            ->whereCompanyId($this->scope->id())
            ->select(array('access_token', 'access_token_secret', 'quickbook_id', 'created_at', 'updated_at', 'refresh_token_expires_in', 'is_payments_connected'))
            ->first();

        return $quickBook;
    }

    public function token()
    {
        return $this->getToken();
    }

    public function getQuickBookId()
    {
        $quickBook = $this->model->whereCompanyId($this->scope->id())->select('quickbook_id')->first();
        return $quickBook->quickbook_id;
    }

    public function deleteToken()
    {
        $quickBook = $this->model->whereCompanyId($this->scope->id())->firstOrFail();

        $userId = null;

		if(Auth::check()) {

			$userId = Auth::user()->id;
		}

		$quickBookHistory = new QuickBookConnectionHistory([
			'company_id' => $quickBook->company_id,
			'quickbook_id' => $quickBook->quickbook_id,
			'token_type' => $quickBook->token_type,
			'action' => 'disconnect',
			'user_id' => $userId,
		]);

		$quickBookHistory->save();

        return $quickBook->delete();
    }

    public function isAccessTokenExpired($accessToken)
    {
        $token = $this->model->where('access_token', $accessToken)->first();
        $expiring_time = strtotime($token->updated_at) + $token->expires_in - self::LAPSE_FOR_ACCESS_TOKEN;
        return ($expiring_time <= time());
    }

    public function isRefreshTokenExpired($refreshToken)
    {
        $token = $this->model->where('access_token_secret', $refreshToken)->first();
        $expiring_time = strtotime($this->updated_at) + $token->refresh_token_expires_in - self::LAPSE_FOR_REFRESH_TOKEN;
        return ($expiring_time <= time());
    }

    public function isAccessTokenSecretExpired($accessTokenSecret)
    {
        return $this->isRefreshTokenExpired($accessTokenSecret);
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

    public function isPaymentsConnected($companyId)
    {
        $token = $this->where->where('company_id', $companyId)->first();
        return $token->is_payments_connected;
    }
}
