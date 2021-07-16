<?php
namespace App\Models;

class DripCampaignEmailAttachment extends BaseModel
{
	protected $fillable = ['drip_campaign_email_id', 'ref_type', 'ref_id', 'new_resource_id', 'company_id'];


	public function scopeWhereDripCampaignEmailId($query, $id)
	{
		$query->where('drip_campaign_email_id', $id);
	}
}