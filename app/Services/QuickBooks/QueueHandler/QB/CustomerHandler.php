<?php
namespace App\Services\QuickBooks\QueueHandler\QB;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\Bill as QBBill;
use App\Repositories\CustomerRepository;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Notification;
use App\Repositories\JobRepository;
use App\Models\QuickbookSyncBatch;
use App\Services\QuickBooks\Facades\SyncRequest;

class CustomerHandler
{
	use CompanyScopeTrait;

	public function __construct(
		CustomerRepository $customerRepo,
		JobRepository $jobRepo,
		Notification $notification
	) {
		$this->customerRepo = $customerRepo;
		$this->jobRepo = $jobRepo;
		$this->notification = $notification;
	}

	public function import($queueJob, $data)
	{
		// DB::beginTransaction();
		try {
			$batch = null;
			if(!ine($data, 'company_id') && !ine($data, 'customer_import_by')) {
				return $queueJob->delete();
			}

			$batchId = ine($data, 'batch_id') ? $data['batch_id']: null;
			$this->setCompanyScope($data['customer_import_by']);

			if($batchId){
				$batch = QuickbookSyncBatch::find($batchId);
				$batch->status = QuickbookSyncBatch::STATUS_SNAPSHOT;
				$batch->save();
			}
			Log::info('Customer Import Start');
			QBCustomer::import($data['company_id']);
			Log::info('Bill Import Start');
			QBBill::dumpImport($data['company_id']);

			if($batch){
				$batch->status = QuickbookSyncBatch::STATUS_ANALYZING;
				$batch->save();
				SyncRequest::submitted($batch);
			}
			Log::info('Import finished');

			// DB::commit();
			return $queueJob->delete();
		} catch(Exception $e) {
			Log::info('Customer Handler Exception');
			// DB::rollback();
			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
				if($batch){
					$batch->status = QuickbookSyncBatch::STATUS_TERMINATED;
					$batch->save();
				}
				$queueJob->delete();
			}
			Log::error($e);
		}
	}
}