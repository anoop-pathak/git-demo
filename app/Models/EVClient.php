<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

class EVClient extends BaseModel
{
	use SoftDeletes;

    protected $table = 'ev_clients';
    protected $fillable = [
		'company_id', 'username', 'access_token', 'refresh_token', 'client_id', 'token_expiration_date'
	];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
