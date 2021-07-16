<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class QuickBookConnectionHistory extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'action', 'token_type', 'quickbook_id', 'user_id'];

    protected $rules = [
		'company_id'	=> 'required',
		'action'	=> 'required',
        'token_type'	=> 'required',
        'quickbook_id'	=> 'required',
	];

    protected $table = 'quickbook_connection_history';

    protected function getRules()
    {
		return $this->rules;
    }

 }