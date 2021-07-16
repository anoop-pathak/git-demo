<?php

namespace App\Presenters;

use Laracasts\Presenter\Presenter;

class CompanyPresenter extends Presenter
{

    /**
     * Full address
     * @return [string] [address]
     */
    public function fullAddress()
    {
        $address = [];
        if ($this->office_address) {
            $address[] = $this->office_address;
        }

        if ($this->office_address_line_1) {
            $address[] = $this->office_address_line_1;
        }

        $address = implode(',<br>', $address);

        if ($this->office_city) {
            $address .= ',<br>' . $this->office_city . ', ';
        }

        if (isset($this->state->name) && ($this->state->name)) {
            $address .= $this->state->name . ', ';
        }

        if ($this->office_zip) {
            $address .= $this->office_zip;
        }

        return $address;
    }

    /**
     * Additional Emails
     * @return [string] [additional_email]
     */
    public function additionalEmail()
    {
        $emails = [];

        if ($this->office_email) {
            $emails[] = $this->office_email;
        }

        if (!empty($this->additional_email)) {
            foreach ($this->additional_email as $email) {
                if (!isset($email)) {
                    continue;
                }

                $emails[] = $email;
            }
        }

        return implode(',', $emails);
    }

    /**
     * Additional Phones
     * @return [string] [additional_phone]
     */
    public function additionalPhone()
    {
        $phones = [];

        if ($this->office_phone) {
            $phones[] = $this->office_phone;
        }

        if (!empty($this->additional_phone)) {
            foreach ($this->additional_phone as $phone) {
                if (!isset($phone)) {
                    continue;
                }

                $phones[] = $phone;
            }
        }

        return implode(',', $phones);
    }

    public function companyAdditionalEmails()
	{
		$emails = (array)$this->additional_email;

		return implode(',', $emails);
	}

	public function companyAdditionalPhone()
	{
		$phones = (array)$this->additional_phone;

		return implode(',', $phones);
	}

	public function licenseNumber()
	{
		$licenseNumbers = $this->licenseNumbers->pluck('license_number')->toArray();

		return implode(', ', $licenseNumbers);
	}
}
