<?php
namespace App\Services\QuickBooks\TwoWaySync\QueueHandler;

use Exception;
use Illuminate\Support\Facades\Log;
use QuickBooks;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use Illuminate\Support\Facades\DB;
use App\Models\QuickbookWebhooks;

class NotificationsHandler
{
	public function handle($queueJob, $meta)
	{

		try {

			DB::beginTransaction();

			$arrayPayload = json_decode($meta['payload'], true);

			// Log::info("Webhook dump original", $arrayPayload, []);

			/**
			 *Payload may have multiple realmId in eventNotifications.
			* so we have to verify which realmId(Company) has enable quickbook two way sync in Setting.
			*/

			foreach ($arrayPayload['eventNotifications'] as $key => $value) {
				$realmId = $value['realmId'];
				QuickBooks::setCompanyScope($realmId);
			}

			if(!empty($arrayPayload['eventNotifications'])) {
				$intuittid = $meta['headers']['intuit-t-id'];

				$webhook = new QuickbookWebhooks;
				$webhook->request_id = $intuittid[0];
				$webhook->headers = json_encode($meta['headers']);
				$webhook->payload = json_encode($arrayPayload);

				$webhook->save();

				QuickBooks::process($arrayPayload, $webhook);
			}

			DB::commit();

			return $queueJob->delete();
		} catch (QuickBookException $e){
			Log::info($e);
			return $queueJob->delete();

		} catch (Exception $e) {

			DB::rollback();
			if($queueJob->attempts() > 2) {

				return $queueJob->delete();
			}
			throw $e;
		}
	}
}