<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriberStage extends Basemodel
{
	use  SoftDeletes;

	protected $fillable = ['company_id', 'subscriber_stage_attribute_id'];

}