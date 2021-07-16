<?php

namespace App\Services\Masking;

use Config;

class DataMasking
{
	public function maskPhone($phoneNumber)
	{
		if ($phoneNumber && $this->isEnable()) {
			$phoneNumber = $this->phoneFormat($phoneNumber);
		}
 		return $phoneNumber;
	}
 	public function maskEmail($email)
	{
		if ($email && $this->isEnable()) {
			$email = $this->emailFormat($email);
		}
 		return $email;
	}
 	public function maskPhoneExtention($ext)
	{
		if ($ext && $this->isEnable()) {
			$ext = $this->extFormat($ext);
		}
 		return $ext;
	}
 	public function enable()
	{
		Config::set('enable_masking', true);
	}
 	public function disable()
	{
		Config::set('enable_masking', false);
	}
 	public function isEnable()
	{
		if(config('enable_masking')) {
			return true;
		}
 		return false;
	}
 	/********** PRIVATE METHODS **********/
 	private function emailFormat($emailAdd)
	{
		if(is_array($emailAdd)) {
			foreach ($emailAdd as $value) {
				$emailParts = explode('@', $value);
				$email[]	= str_repeat("*", strlen($emailParts[0])).'@'.str_repeat("*", strlen($emailParts[1]));
			}
		}else {
			$emailParts = explode('@', $emailAdd);
			$email	 	= str_repeat("*", strlen($emailParts[0])).'@'.str_repeat("*", strlen($emailParts[1]));
		}
 		return $email;
	}
 	private function phoneFormat($phoneNumber)
	{
		$phoneNumber = str_repeat("*", strlen($phoneNumber));
		if(in_array(config('company_country_code'), ['US','BHS','CA','AU'])) {
			$phoneNumber = preg_replace('/([*]{3})([*]{3})([*]{4})/', '($1) $2-$3', $phoneNumber);
		}
 		return $phoneNumber;
	}
 	private function extFormat($ext)
	{
		$ext = str_repeat("*", strlen($ext));
 		return $ext;
	}
} 