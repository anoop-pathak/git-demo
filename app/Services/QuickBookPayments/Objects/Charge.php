<?php

namespace App\Services\QuickBookPayments\Objects;

use App\Services\QuickBookPayments\Objects\CreditCard;

class Charge extends QuickBooksPaymentsObject
{
	protected $amount;
    protected $currency;
    protected $context;
    protected $card;
    protected $attributes = ['amount', 'currency', 'context', 'card'];
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
        if($amount < 0)
            $amount = 0;
        $this->amount = $amount;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }
    /**
     * @param mixed $token
     *
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;
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
    public function setCurrency($currency = 'USD')
    {
        $this->currency = $currency;
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
    public function setContext($context = 'ecommerce')
    {
        if($context == 'ecommerce') {
            $this->context = [
                'isEcommerce' => TRUE,
                'mobile' => FALSE
            ];
        } else if($context == 'mobile') {
            $this->context = [
                'isEcommerce' => FALSE,
                'mobile' => TRUE
            ];
        } else {
            $this->context = [
                'isEcommerce' => TRUE,
                'mobile' => FALSE
            ];
        }
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
    public function setCard(CreditCard $card)
    {
        $this->card = $card->payload();
        return $this;
    }
}