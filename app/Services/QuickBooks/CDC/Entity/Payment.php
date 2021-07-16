<?php
namespace App\Services\QuickBooks\CDC\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use Exception;
use Log;
use App\Models\QuickBook;
use App\Models\QuickBookTask;
use Carbon\Carbon;

class Payment
{
	public function syncQuicbookChanges($interval = 10)
	{
		try {
			$quickbook = QuickBook::get();
			foreach ($quickbook as $qb) {
				try {
					if(!QuickBooks::setCompanyScope(null, $qb->company_id)) {

						Log::info('CDC:Customer - Error unable to set company scope.', [$qb->company_id]);

						continue;
					}

					$isControlledSyncEnabled = QuickBooks::isControlledSyncEnabled();
					$isTwoWaySyncEnabled = QuickBooks::isTwoWaySyncEnabled();

					if (!$isTwoWaySyncEnabled) {

						Log::info('CDC:Customer - Error two way sync is not enabled.', [$qb->company_id]);
						continue;
					}
					// DB::beginTransaction();

					$response = QuickBooks::cdc(['payment'], $interval);

					if ($response->entities && ine($response->entities, 'Payment')) {
						$payments = $response->entities['Payment'];
						foreach ($payments as $payment) {

							$jpPayment = QuickBooks::getJobPaymentByQBId($payment->Id);
							if($jpPayment
								&& $jpPayment->quickbook_id
								&& $payment->SyncToken > $jpPayment->quickbook_sync_token) {

									$updatedTime = new Carbon($payment->MetaData->LastUpdatedTime);
									$updatedTime = $updatedTime->format('Y-m-d H:i:s');

									$meta = [
										'object_id' => $payment->Id,
										'object_type' => QuickBookTask::PAYMENT,
										'operation' => QuickBookTask::UPDATE,
										'company_id' => getScopeId(),
										'realm_id' => $qb->quickbook_id,
										'object_updated_at' => $payment->MetaData->LastUpdatedTime,
										'extra' => [
											'queued_by' => 'cdc'
										],
									];

									QuickBooks::addWebhookEntry($meta);
							} else if ($jpPayment && $payment->SyncToken == '') {

								$meta = [
									'object_id' => $payment->Id,
									'object_type' => QuickBookTask::PAYMENT,
									'operation' => QuickBookTask::DELETE,
									'company_id' => getScopeId(),
									'realm_id' => $qb->quickbook_id,
									'object_updated_at' => $payment->MetaData->LastUpdatedTime,
									'extra' => [
										'queued_by' => 'cdc'
									],
								];

								QuickBooks::addWebhookEntry($meta);

							} else if (!$isControlledSyncEnabled && !$jpPayment) {

								$meta = [
									'object_id' => $payment->Id,
									'object_type' => QuickBookTask::PAYMENT,
									'operation' => QuickBookTask::CREATE,
									'company_id' => getScopeId(),
									'realm_id' => $qb->quickbook_id,
									'extra' => [
										'queued_by' => 'cdc'
									],
								];

								QuickBooks::addWebhookEntry($meta);
							}
						}
					}

					Log::info('CDC:Payment - success', [$qb->company_id]);

					// DB::commit();
				} catch (Exception $e) {

					// DB::rollback();

					Log::warning('CDC:Payment - error', [(string) $e]);
				}
			}

		} catch (Exception $e) {
			Log::error('CDC:Payment Task Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}