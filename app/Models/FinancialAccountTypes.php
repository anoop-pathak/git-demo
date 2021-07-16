<?php
namespace App\Models;

use Carbon\Carbon;

class FinancialAccountTypes extends BaseModel
{
	protected $fillable = ['classification', 'account_type', 'account_type_display_name', 'account_sub_type', 'account_sub_type_display_name'];
}