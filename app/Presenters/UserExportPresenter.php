<?php

namespace App\Presenters;

use Laracasts\Presenter\Presenter;

class UserExportPresenter extends Presenter
{

    /**
     * Additional Phones
     * @return [string] [additional_phone]
     */
    public function additionalPhones()
    {
        $phones = [];

        if ($this->phone) {
            $phones[] = $this->phone;
        }

        if (!empty($this->additional_phone)) {
            foreach ($this->additional_phone as $value) {
                if (!isset($value->phone)) {
                    continue;
                }

                $phones[] = $value->phone;
            }
        }

        return implode(',', $phones);
    }

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

		if($this->address) {
			$address[] = $this->address;
		}

		if($this->address_line_1) {
			$address[] = $this->address_line_1;
		}

		$firstLine = implode(',<br>', $address);

		if($this->city) {
			$secondLine = $this->city;
		}

		if($showStateCode) {
			if(isset($this->state->code) && ($this->state->code )) {
				$secondLine = $secondLine ? $secondLine.', '.$this->state->code : $this->state->code;
			}
		} elseif(isset($this->state->name) && ($this->state->name)) {
			$secondLine = $secondLine ? $secondLine.', '.$this->state->name : $this->state->name;
		}

		if(($showCountry) &&  isset($this->country->name) && ($this->country->name)) {
			$address[] = $brTag.$this->country->name;
			$brTag = null;
		}

		if($this->zip) {
			$secondLine = $secondLine ? $secondLine.', '.$this->zip : $this->zip;
		}

		return $firstLine ? $firstLine.', <br>'.$secondLine : $secondLine;
	}

	/**
	 * Full address in one line
	 * @return [string] [address]
	 */
	public function fullAddressOneLine($showCountry = false, $showStateCode = false)
	{
		$address = $this->fullAddress($showCountry, $showStateCode);

		return str_replace('<br>', ' ', $address);
	}
}
