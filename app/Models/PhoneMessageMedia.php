<?php

namespace App\Models;

use App\Models\PhoneMessage;

class PhoneMessageMedia extends BaseModel {

 	protected $table = 'phone_message_media';

 	protected $fillable = ['sid', 'company_id', 'media_url', 'short_url', 'resource_id', 'type'];

 	public function phoneMessage()
	{
		return $this->belongsTo(PhoneMessage::class, 'sid', 'sid');
	}
}