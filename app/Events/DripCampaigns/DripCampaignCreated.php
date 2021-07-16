<?php namespace App\Events\DripCampaigns;

class DripCampaignCreated
{
	public $dripCampaign;

	function __construct($dripCampaign){
		$this->dripCampaign = $dripCampaign;
	}
}
