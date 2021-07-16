<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Customer;
use ActivityLogs;
use App\Services\Solr\Solr;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Queue;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CustomerObserver
{

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.deleting: Customer', 'App\Observers\CustomerObserver@deleting');
        $event->listen('eloquent.deleted: Customer', 'App\Observers\CustomerObserver@deleted');
        $event->listen('eloquent.updated: Customer', 'App\Observers\CustomerObserver@updated');
        $event->listen('eloquent.restored: Customer', 'App\Observers\CustomerObserver@restored');
        $event->listen('eloquent.created: Customer', 'App\Observers\CustomerObserver@created');
    }

    public function created($customer)
    {
        //maintain activity log.
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::CUSTOMER_CREATED,
            $displayData = array(),
            $meta = array(),
            $customer->id
        );
    }

    // before delete
    public function deleting($customer)
    {
        Job::where('customer_id', $customer->id)->update([
            'deleted_by' => Auth::id(),
            'deleted_at' => Carbon::now()
        ]);

        // save deleted by and delete note
        Customer::where('id', $customer->id)->update([
            'deleted_by' => Auth::user()->id,
            'delete_note' => Request::get('note')
        ]);
    }

    // after delete
    public function deleted($customer)
    {
        $displayData = $customer->toArray();

        //maintain activity log.
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::CUSTOMER_DELETED,
            $displayData,
            $meta = [],
            $customer->id
        );
        $data = [
            'customer_id' => $customer->id,
            'current_user_id' => Auth::id()
        ];
        Queue::push('\App\Handlers\Events\CustomerQueueHandler@customerDeleteSolr', $data);
        Queue::push('\App\Handlers\Events\CustomerQueueHandler@updateWorkflow', $data);
        \App::make('App\Services\DripCampaigns\DripCampaignService')->cancelCustomerCampaign($customer->id);
    }

    // after update
    public function updated($customer)
    {

        //maintain activity log.
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::CUSTOMER_UPDATED,
            $displayData = [],
            $meta = [],
            $customer->id
        );
    }

    // Restored
    public function restored($customer)
    {
        // Restore jobs
        DB::table('customers')->where('id', $customer->id)->update([
            'deleted_at' => null,
            'deleted_by' => null,
            'delete_note' => null,
        ]);

        $data = [
            'customer_id' => $customer->id,
            'current_user_id' => Auth::id()
        ];
        Queue::push('\App\Handlers\Events\CustomerQueueHandler@customerIndexSolr', $data);
        Queue::push('\App\Handlers\Events\CustomerQueueHandler@updateWorkflow', $data);

        $displayData['title'] = 'Customer Restored';
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::CUSTOMER_RESTORED,
            $displayData,
            $metaData = array(),
            $customer->id
        );
    }
}
