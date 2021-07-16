<?php
namespace App\Services\QuickBooks\CDC\Entity;

use QuickBooks;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\QuickBook;
use App\Models\QuickBookTask;
use Carbon\Carbon;
use App\Models\JobCredit;

class CreditMemo
{
	public function syncQuicbookChanges($interval = 10)
	{

		try {
			$quickbook = QuickBook::get();
			foreach ($quickbook as $qb) {
				try {

					if (!QuickBooks::setCompanyScope(null, $qb->company_id)) {

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

					$response = QuickBooks::cdc(['creditmemo'], $interval);

					if ($response->entities && ine($response->entities, 'CreditMemo')) {

						$creditMemos = $response->entities['CreditMemo'];

						foreach ($creditMemos as $creditMemo) {

							$jpCredit = JobCredit::where('quickbook_id', $creditMemo->Id)
								->where('company_id', getScopeId())
								->first();

							if($jpCredit
								&& $jpCredit->quickbook_id
								&& $creditMemo->SyncToken > $jpCredit->quickbook_sync_token) {

									$updatedTime = new Carbon($creditMemo->MetaData->LastUpdatedTime);

									$updatedTime = $updatedTime->format('Y-m-d H:i:s');

									$meta = [
										'object_id' => $creditMemo->Id,
										'object_type' => QuickBookTask::CREDIT_MEMO,
										'operation' => QuickBookTask::UPDATE,
										'company_id' => getScopeId(),
										'realm_id' => $qb->quickbook_id,
										'object_updated_at' => $creditMemo->MetaData->LastUpdatedTime,
										'extra' => [
											'queued_by' => 'cdc'
										],
									];

									QuickBooks::addWebhookEntry($meta);

							} else if ($jpCredit && $creditMemo->SyncToken == '') {

								$meta = [
									'object_id' => $creditMemo->Id,
									'object_type' => QuickBookTask::CREDIT_MEMO,
									'operation' => QuickBookTask::DELETE,
									'company_id' => getScopeId(),
									'realm_id' => $qb->quickbook_id,
									'object_updated_at' => $creditMemo->MetaData->LastUpdatedTime,
									'extra' => [
										'queued_by' => 'cdc'
									],
								];

								QuickBooks::addWebhookEntry($meta);

							} else if (!$isControlledSyncEnabled && !$jpCredit) {

								$meta = [
									'object_id' => $creditMemo->Id,
									'object_type' => QuickBookTask::CREDIT_MEMO,
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

					Log::info('CDC:CreditMemo - success', [$qb->company_id]);

					// DB::commit();

				} catch (Exception $e) {

					// DB::rollback();

					Log::warning('CDC:CreditMemo - error', [(string) $e]);
				}
			}

		} catch (Exception $e) {
			Log::error('CDC:CreditMemo CDC Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}