<?php
namespace App\Models;

class EmailAddress extends BaseModel
{
	protected $fillable = ['company_id', 'email'];

	public function contacts(){
		return $this->belongsToMany(Contact::class, 'contact_email', 'contact_id', 'email_address_id')->withTimestamps();
	}
}