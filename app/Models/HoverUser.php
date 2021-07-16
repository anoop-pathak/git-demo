<?php
namespace App\Models;

class HoverUser extends BaseModel
{
	protected $table = 'hover_users';
	
	protected $fillable = ['first_name', 'last_name', 'hover_user_id', 'email', 'aasm_state', 'acl_template'];
} 