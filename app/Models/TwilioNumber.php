<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class TwilioNumber extends BaseModel
{
	use SortableTrait;
	use SoftDeletes;

	protected $fillable = ['company_id', 'user_id', 'phone_number', 'sid', 'deleted_by', 'zip_code', 'state_code', 'lat', 'long'];

	const COUNTRY_CODE_UK = 'UK';
	const COUNTRY_CODE_GB = 'GB';

	public function company()
	{
		return $this->belongsTo(Company::class, 'company_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}