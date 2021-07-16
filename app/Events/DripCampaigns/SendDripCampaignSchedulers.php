<?php namespace App\Events\DripCampaigns;

class SendDripCampaignSchedulers
{
	public $dripCampaign;

	function __construct($dripCampaign){
		$this->dripCampaign = $dripCampaign;
	}
}