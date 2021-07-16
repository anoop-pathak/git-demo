<?php

namespace App\Models;

use App\Services\QuickBookPayments\Objects\AccessToken;
use App\Services\QuickBooks\Exceptions\QuickBookCompanyConnectionException;

class QuickBook extends BaseModel
{

    protected $table = 'quickbooks';

    protected $fillable = ['company_id', 'quickbook_id', 'access_token', 'access_token_secret', 'expires_in', 'refresh_token_expires_in', 'token_type', 'is_payments_connected'];

    const FIRST_NAME_LAST_NAME = 'first_name_last_name';
    const LAST_NAME_FIRST_NAME = 'last_name_first_name';
    const LAPSE_FOR_TOKENS = 120;
    const CUSTOMER_SNAPSHOT_DURATION = 2; //In days

    /**
     * BelongTo relation in QuickBook Model
     * @return Eloquent Relation
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * This method will save the access token from the AccessToken object
     * @param  AccessToken $accessToken
     * @return Boolean Whether access token is saved or not
     */
    public function saveAccessToken(AccessToken $accessToken)
    {
        $connectionHistory = QuickBookConnectionHistory::where('company_id', getScopeId())
			->latest('id')
			->first();

		if($connectionHistory && $connectionHistory->quickbook_id != $accessToken->getRealmId()) {
			throw new QuickBookCompanyConnectionException(trans('response.error.different_qb_company_not_allowed'));
		}

		$history = QuickBookConnectionHistory::where('quickbook_id', $accessToken->getRealmId())
			->first();

		if($history && $history->company_id != getScopeId()) {

			throw new QuickBookCompanyConnectionException('QuickBook Company already connected with other user.');
		}

        $quickBook = static::firstOrNew((['company_id' => getScopeId()]));
        $quickBook->quickbook_id = $accessToken->getRealmId();
        $quickBook->access_token = $accessToken->getAccessToken();
        $quickBook->access_token_secret = $accessToken->getRefreshToken();
        $quickBook->expires_in = $accessToken->getExpiresIn();
        $quickBook->refresh_token_expires_in = $accessToken->getRefreshTokenExpiresIn();
        $quickBook->token_type = $accessToken->getTokenType();
        $quickBook->is_payments_connected = $accessToken->getWithPaymentsScope();

        $quickBookHistory = new QuickBookConnectionHistory([
			'company_id' => $quickBook->company_id,
			'quickbook_id' => $quickBook->quickbook_id,
			'token_type' => $quickBook->token_type,
			'action' => 'connect',
			'user_id' => \Auth::user()->id,
		]);

		$quickBookHistory->save();

        return $quickBook->save();
    }

    public function getRefreshTokenAttribute()
    {
        return $this->access_token_secret;
    }

    public function meta()
    {
        return $this->hasMany(QuickbookMeta::class, 'quickbook_id', 'id');
    }

    public function isRefreshTokenExpired()
    {
        $expiring_time = strtotime($this->updated_at) + $this->refresh_token_expires_in - self::LAPSE_FOR_TOKENS;
        return ($expiring_time <= time());
    }
    public function isPaymentsConnected()
    {
        return $this->is_payments_connected;
    }
}
