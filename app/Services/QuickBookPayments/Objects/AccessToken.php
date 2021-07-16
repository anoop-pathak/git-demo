<?php

namespace App\Services\QuickBookPayments\Objects;

use App\Models\QbOAuth2AccessToken;
use App\Services\QuickBookPayments\Objects\QuickBooksPaymentsObject;

class AccessToken extends QuickBooksPaymentsObject
{
	protected $token_type;
    protected $access_token;
	protected $expires_in;
	protected $refresh_token;
    protected $x_refresh_token_expires_in;
    protected $refresh_token_expires_in;
    protected $realm_id;
	protected $with_payments_scope;
    protected $attributes = ['access_token', 'refresh_token', 'expires_in', 'refresh_token_expires_in', 'token_type'];
    
    public function makeFromJson($jsonString)
    {
        $this->setPayload(json_decode($jsonString));
        return $this;
    }
    
    public function makeFromEloquentModel(QbOAuth2AccessToken $model)
    {
        foreach ($this->attributes as $attribute) {
            $this->{$attribute} = $model->{$attribute};
        }
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getTokenType()
    {
        return $this->token_type;
    }
    
    /**
     * @param mixed $token_type
     *
     * @return self
     */
    public function setTokenType($token_type)
    {
        $this->token_type = $token_type;
         return $this;
    }
    
    /**
     * @return mixed
     */
    public function getExpiresIn()
    {
        return $this->expires_in;
    }
    
    /**
     * @param mixed $expires_in
     *
     * @return self
     */
    public function setExpiresIn($expires_in)
    {
        $this->expires_in = $expires_in;
         return $this;
    }
    
    /**
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }
    
    /**
     * @param mixed $refresh_token
     *
     * @return self
     */
    public function setRefreshToken($refresh_token)
    {
        $this->refresh_token = $refresh_token;
         return $this;
    }
    
    /**
     * @return mixed
     */
    public function getXRefreshTokenExpiresIn()
    {
        return $this->x_refresh_token_expires_in;
    }
    
    /**
     * @return mixed
     */
    public function getRefreshTokenExpiresIn()
    {
        return $this->refresh_token_expires_in;
    }
    
    /**
     * @param mixed $refresh_token_expires_in
     *
     * @return self
     */
    public function setXRefreshTokenExpiresIn($x_refresh_token_expires_in)
    {
        $this->x_refresh_token_expires_in = $x_refresh_token_expires_in;
        $this->refresh_token_expires_in = $x_refresh_token_expires_in;
         return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }
    
    /**
     * @param mixed $access_token
     *
     * @return self
     */
    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
         return $this;
    }
    
    /**
     * @return mixed
     */
    public function getRealmId()
    {
        return $this->realm_id;
    }
    
    /**
     * @param mixed $realm_id
     *
     * @return self
     */
    public function setRealmId($realm_id)
    {
        $this->realm_id = $realm_id;
         return $this;
    }
    
    /**
     * @return mixed
     */
    public function getWithPaymentsScope()
    {
        return $this->with_payments_scope;
    }
    
    /**
     * @param mixed $with_payments_scope
     *
     * @return self
     */
    public function setWithPaymentsScope($with_payments_scope = false)
    {
        $this->with_payments_scope = $with_payments_scope;
         return $this;
    }
} 
