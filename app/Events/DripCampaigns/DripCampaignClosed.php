<?php namespace App\Events\DripCampaigns;

class DripCampaignClosed
{
	public $dripCampaign;

	function __construct($dripCampaign){
		$this->dripCampaign = $dripCampaign;
	}
}