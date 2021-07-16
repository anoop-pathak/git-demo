<?php
namespace App\Services\QuickBooks\SyncManager;

use App\Models\QuickbookSyncBatch;
use App\Services\QuickBooks\Sync\Customer as SyncCustomerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use App\Models\QuickBook;
use App\Models\QBDesktopUser;
use App\Services\QuickBookDesktop\SyncTask;

class Batch
{
	public function save($input)
	{
		if($input['sync_action'] == 'customers') {
			$customerIds = arry_fu($input['customer_ids']);
			unset($input['customer_ids']);
		} elseif ($input['sync_action'] == 'other') {
			$input = array_merge($input, $this->durationDate($input));
		}

		$connection = $this->getConnectionType();

		$batch = QuickbookSyncBatch::create([
			'company_id' 				=> getScopeId(),
			'status'	 				=> QuickbookSyncBatch::STATUS_SUBMITTED,
			'created_by' 				=> Auth::id(),
			'status_changed_date_time'	=> Carbon::now()->toDateTimeString(),
			'sync_action'				=> $input['sync_action'],
			'sync_scope'				=> isSetNotEmpty($input, 'sync_scope') ?: null,
			'sync_request_meta'			=> $input,
			'connection_type'			=> ine($connection, 'connection_type') ? $connection['connection_type'] : null ,
		]);

		if($input['sync_action'] == 'customers') {
			$batch->selectedCustomers()->sync($customerIds);
		}

		$data = [
			'company_id' => getScopeId(),
			'customer_import_by' => Auth::id(),
			'batch_id' => $batch->id,

		];

		if(ine($connection, 'connection_type')){
			if($connection['connection_type'] == QuickbookSyncBatch::QBO){
				Queue::connection('qbo')->push('App\Services\QuickBooks\QueueHandler\QB\CustomerHandler@import', $data);
			}

			if($connection['connection_type'] == QuickbookSyncBatch::QBD){
				$desktopUser = $connection['qb_destktop'];
				$this->addImportRequest($desktopUser->qb_username, $batch);
			}
		}

		// \JobProgress\QuickBooks\Facades\SyncRequest::submitted($batch);

		return $batch;
	}

	public function listing($filters)
	{
		$query = QuickbookSyncBatch::where('company_id', getScopeId())
			->orderBy('id', 'desc');

		return $query;
	}

	public function getById($id)
	{
		$query = QuickbookSyncBatch::where('company_id', getScopeId())
			->where('id', $id);

		return $query->firstOrFail();
	}

	/***** Private Functions *****/

	private function saveQBSyncCustomers($batch, $jpToQb = true, $qbToJp = true, $checkCustomerIds = false, $customerIds = [])
	{
		$syncCustomerService = app()->make(SyncCustomerService::class);

		if($qbToJp) {
			$syncCustomerService->storeCustomers($batch);
		}
		
		if($jpToQb) {
			$syncCustomerService->storeUnSyncedCustomers($batch, $checkCustomerIds, $customerIds);
		}

		return $batch;
	}

	private function durationDate($input)
	{
		$date = [];
		switch ($input['duration']) {
			case 'WTD':
				$startDate  = Carbon::now()->startOfWeek()->toDateString();
				$endDate 	= Carbon::now()->endOfWeek()->toDateString();
				break;
			case 'MTD':
				$startDate  = Carbon::now()->startOfMonth()->toDateString();
				$endDate 	= Carbon::now()->endOfMonth()->toDateString();
				break;
			case 'YTD':
				$startDate  = Carbon::now()->startOfYear()->toDateString();
				$endDate 	= Carbon::now()->endOfYear()->toDateString();
				break;
			case 'last_month':
				$startDate  = Carbon::now()->startOfMonth()->subMonth()->toDateString();
				$endDate 	= Carbon::now()->subMonth()->endOfMonth()->toDateString();
				break;
			case 'since_inception':
				$startDate  = null;
				$endDate 	= null;
				break;
			case 'custom':
				$startDate  = Carbon::parse($input['start_date'])->toDateString();
				$endDate 	= Carbon::parse($input['end_date'])->toDateString();
				break;
		}
		
		return [
			'start_date' => $startDate,
			'end_date' => $endDate
		];
	}

	private function getConnectionType()
	{
		$data = [];
		$quickbook = QuickBook::where('company_id', getScopeId())->whereNotNull('quickbook_id')->first();

		if($quickbook){
			$data['qb_online'] = $quickbook;
			$data['connection_type'] = QuickbookSyncBatch::QBO;
		}

		$qbDesktop = QBDesktopUser::where('company_id', getScopeId())
				->whereSetupCompleted(true)
				->first();
		if($qbDesktop){
			$data['qb_destktop'] = $qbDesktop;
			$data['connection_type'] = QuickbookSyncBatch::QBD;
		}

		return $data;
	}

	private function addImportRequest($userName, $batch)
	{

		$syncTask = app()->make(SyncTask::class);
		$meta['batch_id'] = $batch->id;

		$syncTask->addDumpTasks($userName, $meta);
	}
}