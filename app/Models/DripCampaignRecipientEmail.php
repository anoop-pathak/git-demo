<?php
namespace App\Models;

class DripCampaignRecipientEmail extends BaseModel
{
	protected $fillable = ['email_campaign_id', 'type', 'email', 'company_id'];
}