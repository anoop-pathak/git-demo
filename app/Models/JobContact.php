<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobContact extends Model
{

    protected $table = 'job_contact';

	protected $fillable = ['job_id', 'contact_id', 'is_primary'];

	public function job()
	{
		return $this->belongsTo(Job::class);
	}
}
