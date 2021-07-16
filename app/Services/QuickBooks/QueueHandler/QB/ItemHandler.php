<?php
namespace App\Services\QuickBooks\QueueHandler\QB;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\Item;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Repositories\SettingsRepository;
use Settings;

class ItemHandler
{
	use CompanyScopeTrait;

	public function defaultItemsCreateTask($queueJob, $data)
	{
		try {
			$userId = $data['current_login_user_id'];
			setAuthAndScope($userId);

			$settings = Settings::get('QBO_ITEMS');

			$defaultItems = [];
			if(!$settings) {
				$defaultItems = [
					'Services',
					'Tax'
				];
			} else {
				if(!ine($settings, 'Services')) {
					$defaultItems[] = 'Services';
				}

				if(!ine($settings, 'Tax')) {
					$defaultItems[] = 'Tax';
				}
			}

			foreach ($defaultItems as $key => $value) {
				$data['item_name'] = $value;
				QBOQueue::addTask(QuickBookTask::QUICKBOOKS_ITEM_CREATE, $data, [
					'object_id' => $key,
					'object' => QuickBookTask::ITEM,
					'action' => "createInQuickBooks",
					'origin' => QuickBookTask::ORIGIN_JP,
					'created_source' => QuickBookTask::SYSTEM_EVENT
				]);
			}
			$queueJob->delete();
		} catch (Exception $e) {

			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
				return $queueJob->delete();
			}

			Log::error($e);
		}
	}

	public function createInQuickBooks($queueJob, $data)
	{
		{
			DB::beginTransaction();
			try {

				$queueStartedAt = Carbon::now()->toDateTimeString();

				$entry = $data['payload'];
				$task = QuickBookTask::find($entry['id']);
				$qbId = null;
				$itemName = $entry['payload']['item_name'];

				$userId = $data['user_id'];
				$this->setCompanyScope($userId);

				$task = QBOQueue::markSuccess($task->id);
				$item = Item::findOrCreateItem($itemName);
				$qbId = $item['id'];

				DB::commit();

				$settings = Settings::get('QBO_ITEMS');

				$value[$itemName] = [
					'qb_id' => $qbId
				];

				if($settings && $qbId) {
					$value = (array)$settings;
					$value[$itemName] = [
						'qb_id' => $qbId
					];
				}

				$data = [
					'key' => 'QBO_ITEMS',
					'name' => 'QBO ITEMS',
					'value' => $value,
					'company_id' => getScopeId()
				];

				app(SettingsRepository::class)->saveSetting($data);

				if($queueJob->attempts() == 1) {
					$task->queue_started_at = $queueStartedAt;
				}

				$task->qb_object_id = $qbId;

				$task->queue_attempts = $queueJob->attempts();
				$task->queue_completed_at = Carbon::now()->toDateTimeString();
				$task->save();

				return $queueJob->delete();
			} catch (Exception $e) {
				DB::rollback();

				QBOQueue::markFailed($task->id, (string) $e);

				$task->queue_attempts = $queueJob->attempts();

				if($queueJob->attempts() == 1) {
					$task->queue_started_at = $queueStartedAt;
				}

				if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
					$task->queue_completed_at = Carbon::now()->toDateTimeString();
					$task->save();

					return $queueJob->delete();
				}

				$task->save();
			}
		}
	}
}