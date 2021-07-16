<?php

namespace App\Services\QuickBookPayments\Objects;

class CapturedCharge extends QuickBooksPaymentsObject
{
	protected $created;
	protected $status;
	protected $amount;
	protected $currency;
	protected $card;
	protected $capture;
	protected $appType;
	protected $avsStreet;
	protected $avsZip;
	protected $cardSecurityCodeMatch;
	protected $id;
	protected $context;
	protected $authCode;
	protected $attributes = ['created', 'status', 'amount', 'currency', 'card', 'capture', 'appType', 'avsStreet', 'avsZip', 'cardSecurityCodeMatch', 'id', 'context', 'authCode'];
    
    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    /**
     * @param mixed $created
     *
     * @return self
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }
    
    /**
     * @param mixed $status
     *
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }
    
    /**
     * @param mixed $amount
     *
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }
    
    /**
     * @param mixed $currency
     *
     * @return self
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getCard()
    {
        return $this->card;
    }
    
    /**
     * @param mixed $card
     *
     * @return self
     */
    public function setCard($card)
    {
        $this->card = $card;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getCapture()
    {
        return $this->capture;
    }
    
    /**
     * @param mixed $capture
     *
     * @return self
     */
    public function setCapture($capture)
    {
        $this->capture = $capture;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAppType()
    {
        return $this->appType;
    }
    
    /**
     * @param mixed $appType
     *
     * @return self
     */
    public function setAppType($appType)
    {
        $this->appType = $appType;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAvsStreet()
    {
        return $this->avsStreet;
    }
    
    /**
     * @param mixed $avsStreet
     *
     * @return self
     */
    public function setAvsStreet($avsStreet)
    {
        $this->avsStreet = $avsStreet;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAvsZip()
    {
        return $this->avsZip;
    }
    
    /**
     * @param mixed $avsZip
     *
     * @return self
     */
    public function setAvsZip($avsZip)
    {
        $this->avsZip = $avsZip;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getCardSecurityCodeMatch()
    {
        return $this->cardSecurityCodeMatch;
    }
    
    /**
     * @param mixed $cardSecurityCodeMatch
     *
     * @return self
     */
    public function setCardSecurityCodeMatch($cardSecurityCodeMatch)
    {
        $this->cardSecurityCodeMatch = $cardSecurityCodeMatch;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * @param mixed $id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }
    
    /**
     * @param mixed $context
     *
     * @return self
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAuthCode()
    {
        return $this->authCode;
    }
    
    /**
     * @param mixed $authCode
     *
     * @return self
     */
    public function setAuthCode($authCode)
    {
        $this->authCode = $authCode;
        return $this;
    }
    
    /**
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    /**
     * @param mixed $attributes
     *
     * @return self
     */
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }
}