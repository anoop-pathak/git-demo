<?php
namespace App\Models;

class EmailBounceSummary extends BaseModel {
	protected $table = 'email_bounce_summary';
	protected $fillable = ['type', 'sub_type', 'email_address' , 'status', 'reason'];
}