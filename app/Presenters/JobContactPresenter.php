<?php

namespace App\Presenters;

use Laracasts\Presenter\Presenter;

class JobContactPresenter extends Presenter
{
    public function jobContactFullAddress()
    {
        $addressObj = $this->address;
        if(!$addressObj) return;
        $address[] = $addressObj->address;
        $address[] = $addressObj->address_line_1;
		$address[] = $this->jobContactState($addressObj);
		$address[] = $this->jobContactCountry($addressObj);
		$address[] = $addressObj->zip;

        return implode(', ', array_filter($address));
    }

    public function jobContactState($address)
    {

        if (!($state = $address->state)) {
            return null;
        }

        return $state->name;
    }

    public function jobContactCountry($address)
    {
        if (!($country = $address->country)) {
            return null;
        }

        return $country->name;
    }
}
