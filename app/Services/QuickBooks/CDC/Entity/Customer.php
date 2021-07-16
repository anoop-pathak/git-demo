<?php
namespace App\Services\QuickBooks\CDC\Entity;

use QuickBooks;
use Exception;
use Log;
use App\Models\QuickBook;
use App\Models\Customer as CustomerModel;
use App\Models\QuickBookTask;
use Carbon\Carbon;
use App\Models\Job;

class Customer
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

					$response = QuickBooks::cdc(['customer'], $interval);

					if ($response->entities && ine($response->entities, 'Customer')) {

						$customers = $response->entities['Customer'];

						foreach ($customers as $customer) {

							if($customer->Job == 'true') {
								// Users can't update sub customers beyond 2nd level
								// 2nd  level is project level
								if($customer->Level > 2) {

									return;
								}

								$jpJob = Job::where('quickbook_id', $customer->Id)
									->where('company_id', getScopeId())
									->first();

								if ($jpJob && $jpJob->quickbook_id
									&& $customer->SyncToken > $jpJob->quickbook_sync_token
								) {

									$updatedTime = new Carbon($customer->MetaData->LastUpdatedTime);

									$updatedTime = $updatedTime->format('Y-m-d H:i:s');

									$meta = [
										'object_id' => $customer->Id,
										'object_type' => QuickBookTask::CUSTOMER,
										'operation' => QuickBookTask::UPDATE,
										'company_id' => getScopeId(),
										'realm_id' => $qb->quickbook_id,
										'object_updated_at' => $updatedTime,
										'extra' => [
											'queued_by' => 'cdc'
										],
									];

									QuickBooks::addWebhookEntry($meta);
								}

								if (!$isControlledSyncEnabled && empty($jpJob)) {

									$meta = [
										'object_id' => $customer->Id,
										'object_type' => QuickBookTask::CUSTOMER,
										'operation' => QuickBookTask::CREATE,
										'company_id' => getScopeId(),
										'realm_id' => $qb->quickbook_id,
										'extra' => [
											'queued_by' => 'cdc'
										],
									];

									QuickBooks::addWebhookEntry($meta);
								}

							} else if ($customer->Job == 'false') {
								$jpCustomer = CustomerModel::where('quickbook_id', $customer->Id)
									->where('company_id', getScopeId())
									->first();

								if ($jpCustomer
									&& $jpCustomer->quickbook_id
									&& $customer->SyncToken > $jpCustomer->quickbook_sync_token
								) {
									$updatedTime = new Carbon($customer->MetaData->LastUpdatedTime);

									$updatedTime = $updatedTime->format('Y-m-d H:i:s');

									$meta = [
										'object_id' => $customer->Id,
										'object_type' => QuickBookTask::CUSTOMER,
										'operation' => QuickBookTask::UPDATE,
										'company_id' => getScopeId(),
										'realm_id' => $qb->quickbook_id,
										'object_updated_at' => $updatedTime,
										'extra' => [
											'queued_by' => 'cdc'
										],
									];
									QuickBooks::addWebhookEntry($meta);
								}

								if (!$isControlledSyncEnabled && empty($jpCustomer)) {

									$meta = [
										'object_id' => $customer->Id,
										'object_type' => QuickBookTask::CUSTOMER,
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
					}

					Log::info('CDC:Customer - success', [$qb->company_id]);

					// DB::commit();

				} catch (Exception $e) {

					// DB::rollback();

					Log::warning('CDC:Customer - error', [(string) $e]);
				}
			}

		} catch (Exception $e) {
			Log::error('CDC:Customer Task Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}