<?php
namespace App\Services\QuickBooks\CDC\Entity;

use QuickBooks;
use Exception;
use Log;
use App\Models\QuickBook;
use App\Models\QuickBookTask;
use Carbon\Carbon;

class Invoice
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

					$response = QuickBooks::cdc(['invoice'], $interval);

					if ($response->entities && ine($response->entities, 'Invoice')) {

						$invoices = $response->entities['Invoice'];

						foreach ($invoices as $invoice) {

							$jpInvoice = QuickBooks::getJobInvoiceByQBId($invoice->Id);

							if($jpInvoice
								&& $jpInvoice->quickbook_invoice_id
								&& $invoice->SyncToken > $jpInvoice->quickbook_sync_token) {

									$updatedTime = new Carbon($invoice->MetaData->LastUpdatedTime);

									$updatedTime = $updatedTime->format('Y-m-d H:i:s');

									$meta = [
										'object_id' => $invoice->Id,
										'object_type' => QuickBookTask::INVOICE,
										'operation' => QuickBookTask::UPDATE,
										'company_id' => getScopeId(),
										'realm_id' => $qb->quickbook_id,
										'object_updated_at' => $updatedTime,
										'extra' => [
											'queued_by' => 'cdc'
										],
									];

									QuickBooks::addWebhookEntry($meta);

							} else if ($jpInvoice && $invoice->SyncToken == '') {
								$updatedTime = new Carbon($invoice->MetaData->LastUpdatedTime);
								$meta = [
									'object_id' => $invoice->Id,
									'object_type' => QuickBookTask::INVOICE,
									'operation' => QuickBookTask::DELETE,
									'company_id' => getScopeId(),
									'realm_id' => $qb->quickbook_id,
									'object_updated_at' => $updatedTime,
									'extra' => [
										'queued_by' => 'cdc'
									],
								];

								QuickBooks::addWebhookEntry($meta);

							} else if (!$isControlledSyncEnabled && !$jpInvoice) {

								$meta = [
									'object_id' => $invoice->Id,
									'object_type' => QuickBookTask::INVOICE,
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

					Log::info('CDC:Invoice - success', [$qb->company_id]);

					// DB::commit();
				} catch (Exception $e) {
					// DB::rollback();
					Log::warning('CDC:Invoice - error', [(string) $e]);
				}
			}

		} catch (Exception $e) {
			Log::error('CDC:Invoice Task Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}