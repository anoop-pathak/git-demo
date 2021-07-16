<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class HoverClient extends BaseModel {

 	protected $table = 'hover_clients';
    
    use SoftDeletes;
 	
 	protected $fillable = ['company_id', 'created_by', 'access_token', 'refresh_token', 'owner_id', 'owner_type', 'access_token_created_at', 'webhook_id'];
 	public function company()
	{
		return $this->belongsto(company::class);
	}
} 