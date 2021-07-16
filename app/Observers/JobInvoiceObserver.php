<?php

namespace App\Observers;

use App\Models\ActivityLog;
use ActivityLogs;
use QBDesktopQueue;
use Illuminate\Support\Facades\Auth;
use Request;

class JobInvoiceObserver
{

    //here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.deleting: JobInvoice', 'App\Observers\JobInvoiceObserver@deleting');
        $event->listen('eloquent.deleted: JobInvoice', 'App\Observers\JobInvoiceObserver@deleted');
    }

    //before delete
    public function deleting($invoice)
    {
        $invoice->deleted_by = \Auth::id();
        $invoice->reason = Request::get('reason') ?: null;
        $invoice->save();
    }

    //after delete
    public function deleted($invoice)
    {
        $job = $invoice->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;
        $metaData = $this->setMetaData($invoice);
        $displayData = $this->setDisplayData($invoice);

        //maintain log for Invoice deleted event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_INVOICE_DELETED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );

        // QBDesktopQueue::deleteInvoice($invoice->id);
    }

    private function setMetaData($invoice)
    {
        $metaData = [];
        $metaData['invoice_id'] = $invoice->id;
        return $metaData;
    }

    private function setDisplayData($invoice)
    {
        $displayData = [];
        $displayData['invoice_id'] = $invoice->id;
        $displayData['invoice_number'] = $invoice->invoice_number;
        $displayData['title'] = $invoice->title;
        $displayData['reason'] = $invoice->reason;

        return $displayData;
    }
}
