<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QBDesktopQueueModel extends Model
{
    protected $table = 'quickbooks_queue';

	public function scopeWorksheet($query)
	{
		$query->where('qb_action', 'EstimateAdd');
	}
}