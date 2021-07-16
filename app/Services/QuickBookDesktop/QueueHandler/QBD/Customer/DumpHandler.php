<?php
namespace App\Services\QuickBookDesktop\QueueHandler\QBD\Customer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBookDesktop\QueueHandler\BaseTaskHandler;
use App\Services\QuickBookDesktop\CDC\Customer as CDCCustomer;
use App\Services\QuickBooks\Sync\Customer as SyncCustomerService;
use App\Models\QuickbookSyncBatch;

class DumpHandler extends BaseTaskHandler
{
    public function __construct(CDCCustomer $entity)
    {
        $this->entity = $entity;
    }

    function synch($task, $meta)
    {
        $data = $this->entity->dumpParse($meta['xml']);
        $entities = $data['entities'];
        $customerIds = $data['customer_ids'];
        $extra  = $meta['extra'];
        $batchId = ine($extra, 'batch_id') ? $extra['batch_id'] : null;

        if($batchId
            && !$task->iterator_id
        ){
            Log::info('Update Batch Status:Taking Snapshot');
            $batch = QuickbookSyncBatch::find($batchId);
            $batch->status = QuickbookSyncBatch::STATUS_SNAPSHOT;
            $batch->save();
        }

        if(!$task->iterator_id){
            DB::table('qbo_customers')->where('company_id', getScopeId())->delete();
        }

        if(!empty($customerIds)){
            DB::table('qbo_customers')
                ->where('company_id', getScopeId())
                ->whereIn('qb_id', $customerIds)
                ->delete();
        }

        if(!empty($entities)){
            DB::Table('qbo_customers')->insert($entities);
        }
        Log::info('Inside Dump Handler');
        Log::info($batchId);
        Log::info($meta['idents']['iteratorRemainingCount']);
        if($batchId
            && empty($meta['idents']['iteratorRemainingCount'])
        ){
        Log::info('Inside Dump Handler: Analysing Sync Task');
            $batch = QuickbookSyncBatch::find($batchId);
            $batch->status = QuickbookSyncBatch::STATUS_ANALYZING;
            $batch->save();

            $syncCustomerService = app()->make(SyncCustomerService::class);
            $syncCustomerService->mappingCustomers($batch);
            Log::info('Inside Dump Handler: Analysing Sync Task Complete');
            $batch->status = QuickbookSyncBatch::STATUS_AWAITING;
            $batch->save();
        }
        Log::info('Dump Update Finished');
    }
}