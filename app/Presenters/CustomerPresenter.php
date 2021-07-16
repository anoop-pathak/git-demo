<?php

namespace App\Presenters;

use App\Models\Customer;
use Laracasts\Presenter\Presenter;

class CustomerPresenter extends Presenter
{


    /**
     * Get Salesman / Rep
     * @return Customer Salesman
     */
    public function salesman($showDefault = true)
    {
        return ($rep = $this->rep) ? $rep->full_name : 'Unassigned';
    }

    /**
     * Get Phone Numbers
     * @return Array
     */
    public function phoneNumbers()
    {
        $labels = config('jp.phone');
        //set all label
        foreach ($labels as $label) {
            $phone[$label] = null;
        }
        //set number corresponding there level
        foreach ($this->phones as $value) {
            $phone[ucfirst(trim($value['label']))][] = $value['number'];
        }
        // number implode into level key
        foreach ($phone as $key => $value) {
            $phone[$key] = !is_null($value) ? implode(', ', $value) : null;
        }

        return $phone;
    }

    public function fullNameAndSecName()
    {
        $fullName = $this->full_name;

        //append secondary name
        if ($scondaryName = $this->secondaryFullName()) {
            $fullName .= ', ' . $scondaryName;
        }

        return $fullName;
    }

    public function secondaryFullName()
    {
        if ($this->is_commercial) {
            return null;
        }

        $secondary = $this->secondaryNameContact;

        if (!$secondary) {
            return null;
        }

        return $secondary->full_name;
    }

    public function showCustomerPhone()
	{

		$phone = $this->firstPhone;

		if(!$phone) return null;

		return $phone->number;
	}

	public function showCustomerCountryCode()
	{
		$address = $this->address;
		$countryCode = null;
		if ($address && $address->country) {
			$countryCode = $address->country->code;
		}

		if (!$countryCode) {
			$countryCode = config('company_country_code');
		}

		return $countryCode;
	}

    public function showCanvessor()
	{
		$canvesser = $this->canvesser;
		if(!$canvesser) return null;

		return $canvesser->full_name;
	}

	public function showCallCenterRep()
	{
		$callCenterRep = $this->callCenterRep;
		if(!$callCenterRep) return null;

		return $callCenterRep->full_name;
	}
}
