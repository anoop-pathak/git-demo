<?php
namespace App\Services\QuickBooks\JPSystemEventHandlers;

use App\Services\QuickBooks\Facades\Attachable as QBAttachable;
use App\Models\QuickBookTask;
use App\Models\VendorBill;
use App\Models\Attachable;

class AttachmentEventHandler {

	public function subscribe($event) {
		$event->listen('JobProgress.Events.AttachmentCreated', 'App\Services\QuickBooks\JPSystemEventHandlers\AttachmentEventHandler@createAttachment');
		$event->listen('JobProgress.Events.AttachmentDeleted', 'App\Services\QuickBooks\JPSystemEventHandlers\AttachmentEventHandler@deleteAttachment');
	}

	public function createAttachment($event)
	{
		$eventMeta = $event->meta;

		$id = $eventMeta['vendor_bill_id'];

		$bill = VendorBill::with(['attachments'])->find($id);

		if($bill && $bill->attachments) {

			foreach ($bill->attachments as $attachment) {

				//check if its already no linked on QBO
				$attachable = Attachable::where('company_id', $bill->company_id)
		    		->where('jp_attachment_id', $attachment->id)
		    		->where('jp_object_id', $bill->id)
		    		->first();

	    		if(!$attachable){
					$attachable = Attachable::create([
						'object_type' => QuickBookTask::BILL,
						'jp_object_id' => $bill->id,
						'jp_attachment_id' => $attachment->id,
						'company_id' => $bill->company_id,
						'customer_id' => $bill->company_id,
						'job_id' => $bill->job_id,
					]);
	    		}
	    		if(!$attachable->quickbook_id){
					QBAttachable::createTask($attachable->id, QuickBookTask::CREATE, QuickBookTask::SYSTEM_EVENT, QuickBookTask::ORIGIN_JP);
	    		}
			}
		}
    }

    public function deleteAttachment($event)
    {
    	$ids = $event->ids;

    	$attachables = Attachable::where('company_id', getScopeId())
    		->whereIn('jp_attachment_id', (array) $ids)
    		->get();

    	if(!$attachables->isEmpty()){
			foreach ($attachables as $attachable) {
				if(!$attachable->quickbook_id){
					$attachable->delete();
					continue;
				}

    			QBAttachable::createTask($attachable->id, QuickBookTask::DELETE, QuickBookTask::SYSTEM_EVENT, QuickBookTask::ORIGIN_JP);
    		}
    	}

    }
}
