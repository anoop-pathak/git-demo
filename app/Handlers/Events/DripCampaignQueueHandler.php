<?php
namespace App\Handlers\Events;

use Illuminate\Support\Facades\Log;

class DripCampaignQueueHandler {

	public function fire($job, $dripCampaign)
	{
		try {

			\Artisan::call('command:send_drip_campaign_scheduler', ['drip_campaign_id' => $dripCampaign['id']]);

		} catch(\Exception $e) {

			Log::error('Drip Campaign Queue Handler error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}