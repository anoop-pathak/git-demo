<?php
namespace App\Models;

class DripCampaignEmail extends BaseModel
{
	protected $fillable = ['drip_campaign_id', 'company_id', 'email_template_id', 'subject', 'content'];

	public function recipients()
    {
        return $this->hasMany(DripCampaignRecipientEmail::class, 'email_campaign_id');
    }

    public function attachments()
    {
		return $this->belongsTomany(Resource::class,'drip_campaign_email_attachments','drip_campaign_email_id','new_resource_id');
	}
}