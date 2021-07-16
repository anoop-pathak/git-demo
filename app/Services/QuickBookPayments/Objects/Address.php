<?php

namespace App\Services\QuickBookPayments\Objects;

class Address extends QuickBooksPaymentsObject
{
    private $region;
    private $postalCode;
    private $streetAddress;
    private $country;
    private $city;
    private $attributes = ['region', 'postalCode', 'streetAddress', 'country', 'city'];
    
    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param mixed $region
     *
     * @return self
     */
    public function setRegion($region)
    {
        $this->region = $region;
         return $this;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param mixed $postalCode
     *
     * @return self
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
         return $this;
    }

    /**
     * @return mixed
     */
    public function getStreetAddress()
    {
        return $this->streetAddress;
    }

    /**
     * @param mixed $streetAddress
     *
     * @return self
     */
    public function setStreetAddress($streetAddress)
    {
        $this->streetAddress = $streetAddress;
         return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     *
     * @return self
     */
    public function setCountry($country)
    {
        $this->country = $country;
         return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     *
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;
         return $this;
    }
} 