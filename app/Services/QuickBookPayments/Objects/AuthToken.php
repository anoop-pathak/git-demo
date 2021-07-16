<?php

namespace App\Services\QuickBookPayments\Objects;

use App\Services\QuickBookPayments\Objects\QuickBooksPaymentsObject;

class AuthToken extends QuickBooksPaymentsObject
{
    protected $code;
    protected $redirect_uri;
    protected $grant_type;
    protected $realm_id;
    protected $state;
    protected $attributes = ['code', 'redirect_uri', 'grant_type'];
    
    public static function getFromCallbackUrl($url)
    {
        $queryParams = parse_url($url, PHP_URL_QUERY);
        parse_str($queryParams, $query);
        return $query;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     *
     * @return self
     */
    public function setCode($code)
    {
        $this->code = $code;
         return $this;
    }

    /**
     * @return mixed
     */
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    /**
     * @param mixed $redirect_uri
     *
     * @return self
     */
    public function setRedirectUri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;
         return $this;
    }

    /**
     * @return mixed
     */
    public function getGrantType()
    {
        return $this->grant_type;
    }

    /**
     * @param mixed $grant_type
     *
     * @return self
     */
    public function setGrantType($grant_type)
    {
        $this->grant_type = $grant_type;
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
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return mixed
     */
    public function getWhetherPaymentsConnectedFromState()
    {
        return property_exists($this->state, 'with_payments') ? $this->state->with_payments : FALSE; 
    }

    /**
     * @param mixed $state
     *
     * @return self
     */
    public function setState($state)
    {
        $this->state = json_decode($state);
        return $this;
    }
} 