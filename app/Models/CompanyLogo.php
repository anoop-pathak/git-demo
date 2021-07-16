<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class CompanyLogo extends Eloquent 
{
	protected $fillable = ['company_id','small_logo', 'large_logo'];
	protected $uploadLogoRule = [
		'logo'      => 'required|mimes:jpeg,png,jpg',
		'logo_type' => 'required|in:small_logo,large_logo'
	];
	
	protected function getUploadLogoRule()
	{
		return $this->uploadLogoRule;
	}
} 