<?php
namespace App\Events\DripCampaigns;

class DripCampaignCanceled
{
	public $dripCampaign;

	function __construct($dripCampaign){
		$this->dripCampaign = $dripCampaign;
	}
}