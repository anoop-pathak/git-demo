<?php
namespace App\Handlers\Events\NotificationEventHandlers;

use MobileNotification;
use App\Models\Worksheet;
use App\Repositories\NotificationsRepository;
use Illuminate\Support\Facades\DB;

class QBDWorksheetFailedNotificationEventHandler {
	function __construct(NotificationsRepository $repo) {
		$this->repo = $repo;
	}
	public function handle($event) {
		$worksheetId = $event->worksheetId;
		$worksheet = Worksheet::find($worksheetId);
		if(!$worksheet) return false;
		$queueId = $event->queueId;
		$jobId = $worksheet->job_id;

		$job   = $worksheet->job;
		if(!$job) return false;
		$customer = $job->customer;
		if(!$customer) return false;
		$customerId = $worksheet->customer_id;
		$type = 'worksheet';
		$title = "QBD worksheet ({$worksheet->name}) Sync Failed";
		$messageContent = "There seems to be a mismatch between one (or more) items used in the worksheet from JobProgress' List and those in QBD's Item List. Please re-sync those items from settings to proceed.";

		$meta = [
			'action'   => 'edit',
			'sub_type' => 'qbd_sync_failed',
			'customer_id'  => $customer->id,
			'worksheet_id' => $worksheet->id,
			'worksheet_type' => $worksheet->type,
			'job_id' => $worksheet->job_id,
			'job_number' => $job->number,
			'multi_job'  => $job->multi_job,
			'job_parent_id' => $job->parent_id,
			'customer_id'   => $customer->id,
			'customer_name' => $customer->full_name,
			'customer_name_mobile' => $customer->full_name_mobile,
			'queue_id' => $event->queueId,
			'job_resource_id' => $job->getResourceId(),
			'company_id'  => $job->company_id,
		];
		MobileNotification::send((array)$worksheet->sync_on_qbd_by, $title, $type, $messageContent, $meta);
		$this->sendNotification(\User::find(1), (array)$worksheet->sync_on_qbd_by, $title, $worksheet, $meta);
		DB::table('quickbooks_queue')->where('quickbooks_queue_id', $queueId)->update(['custom_error_msg' => $messageContent]);
	}
	public function sendNotification($sender, $recipients, $subject, $worksheet, $body = array()) {
		try{
			$this->repo->notification($sender, $recipients, $subject, $worksheet, $body);
		}catch(\Exception $e){

			//Exception
		}
	}
}