<?php

namespace App\Services\QuickBookPayments\Objects;

use App\Exceptions\QuickBookPayments\CreditCardInvalidException;

class CreditCard extends QuickBooksPaymentsObject
{
	protected $name;
	protected $cvc;
	protected $number;
	protected $address;
	protected $expYear;
	protected $expMonth;
    protected $attributes = ['name', 'cvc', 'number', 'expYear', 'expMonth'];
    public function __construct(array $data)
    {
        $this->setPayload($data);
    }
    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param mixed $name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getCvc()
    {
        return $this->cvc;
    }
    /**
     * @param mixed $cvc
     *
     * @return self
     */
    public function setCvc($cvc)
    {
        if(strlen($cvc) < 3 || strlen($cvc) > 4)
            throw new \Exception("Invalid CVC provided");
        
        $this->cvc = $cvc;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }
    /**
     * @param mixed $number
     *
     * @return self
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * @param mixed $address
     *
     * @return self
     */
    public function setAddress(Address $address)
    {
        $this->address = $address;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getExpYear()
    {
        return $this->expYear;
    }
    /**
     * @param mixed $expYear
     *
     * @return self
     */
    public function setExpYear($expYear)
    {
        $this->expYear = $expYear;
        return $this;
    }
    /**
     * @return mixed
     */
    public function getExpMonth()
    {
        return $this->expMonth;
    }
    /**
     * This Setter will set the month in this format 01, 06 rather than 1, 6
     * @param mixed $expiryMonth
     * @return self
     */
    public function setExpMonth($expiryMonth)
    {
        if($expiryMonth < 1 || $expiryMonth > 12) {
            throw new CreditCardInvalidException("Invalid Month Provided");
        }
        $this->expMonth = sprintf("%02s", $expiryMonth);
        
        return $this;
    }
}