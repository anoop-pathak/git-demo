<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;

class PhoneMessage extends BaseModel {

    const MESSAGESIZE = 160;

 	use SortableTrait;

 	protected $fillable = ['sid', 'company_id', 'from_number', 'to_number', 'send_by', 'body', 'status', 'media_urls', 'customer_id', 'job_id'];

 	public function media(){
		return $this->hasMany(PhoneMessageMedia::class, 'sid', 'sid');
	}

 	public function customer(){
		return $this->belongsTo(Customer::class, 'customer_id');
	}

 	public function job(){
		return $this->belongsTo(Job::class, 'job_id');
	}

	public function message()
	{
		return $this->belongsTo(Message::class, 'message_id');
	}

	public function messageThread()
	{
		return $this->belongsTo(MessageThread::class, 'message_thread_id');
	}
}