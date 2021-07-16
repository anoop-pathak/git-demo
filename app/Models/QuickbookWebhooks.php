<?php
namespace App\Models;

class QuickbookWebhooks extends BaseModel {

    protected $fillable = ['request_id', 'headers', 'payload'];

    protected $rules = [
		'request_id'	=> 'required',
		'headers'	=> 'required',
		'payload'	=> 'required',
	];

	protected function getRules() {
		return $this->rules;
	}
 }