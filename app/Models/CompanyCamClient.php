<?php

namespace App\Models;

class CompanyCamClient extends BaseModel
{

    protected $fillable = ['company_id', 'token', 'username', 'status', 'error'];

    protected $hidden = ['token'];

	public function company()
	{
		return $this->belongsTo(Company::class);
	}

}
