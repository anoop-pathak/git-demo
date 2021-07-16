<?php
namespace App\Models;

class CompanyLicenseNumber extends BaseModel {
	protected $table = 'company_license_numbers';

	protected $fillable = ['company_id','position', 'license_number', 'created_by', 'updated_by'];
}