<?php

namespace App\Presenters;

use App\Models\Address;
use Laracasts\Presenter\Presenter;

class AddressPresenter extends Presenter
{

    /**
     * Full address
     * @return [string] [address]
     */
    public function fullAddress($showCountry = false, $showStateCode = false)
    {
        $address = [];
        $firstLine = '';
        $secondLine = '';
        $brTag = null;
        $address = [];

        if ($this->address) {
            $address[] = $this->address;
        }

        if ($this->address_line_1) {
            $address[] = $this->address_line_1;
        }

        $firstLine = implode(',<br>', $address);

        if ($this->city) {
            $secondLine = $this->city;
        }

        if($showStateCode) {
            if(isset( $this->state->code ) && ( $this->state->code )) {
                $secondLine = $secondLine ? $secondLine.', '.$this->state->code : $this->state->code;
            }
        } elseif(isset($this->state->name) && ($this->state->name)) {
            $secondLine = $secondLine ? $secondLine . ', ' . $this->state->name : $this->state->name;
        }

        if (($showCountry) && isset($this->country->name) && ($this->country->name)) {
            $address[] = $brTag . $this->country->name;
            $brTag = null;
        }

        if ($this->zip) {
            $secondLine = $secondLine ? $secondLine . ', ' . $this->zip : $this->zip;
        }

        return $firstLine ? $firstLine . ', <br>' . $secondLine : $secondLine;
    }

    /**
     * Full address in one line
     * @return [string] [address]
     */
    public function fullAddressOneLine($showCountry = false, $showStateCode = false)
    {
        $address = $this->fullAddress($showCountry);

        return str_replace('<br>', ' ', $address);
    }

    /**
     * Get weather address
     * @return Address
     */
    public function weatherAddress()
    {
        $address = $this->city;

        if ($state = $this->state) {
            $address .= ($address) ? ', ' . $state->code : $state->code;
        }

        if ($country = $this->country) {
            $address .= ($address) ? ', ' . $country->code : $country->code;
        }

        return $address;
    }

    public function stateName()
    {
        $stateName = null;

        if (($state = $this->state)) {
            $stateName = $state->name;
        }

        return $stateName;
    }

    public function stateCode()
    {
        $code = null;

        if (($state = $this->state)) {
            $code = $state->code;
        }

        return $code;
    }

    public function countryName()
    {
        $countryName = null;
        if (($country = $this->country)) {
            $countryName = $country->name;
        }

        return $countryName;
    }
}
