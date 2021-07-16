<?php
namespace App\Services\QuickBooks;

use App\Models\QuickBookTask;
use Illuminate\Support\Facades\Log;
use Auth;
use Exception;
use QuickBooks;
use Queue;
use App\Models\Subscription;
use Carbon\Carbon;

/**
 * @author Ankit <ankit@logicielsolutions.co.in>
 * This class polls tasks from quickbook tasks table one by one
 * and send them to queue with resolved handler.
 */
class TaskScheduler {

    public function schedule($howMany)
	{
        try {

            $tasks = null;

            //lock one pending tasks
            app('db')->transaction(function() use (&$tasks, $howMany) {

                $tasks = $this->pollTasks($howMany);

                $task_ids = $tasks->pluck('id')->toArray();

                // Log::info("processed tasks", [$task_ids]);
                if(sizeof($task_ids) > 0){
                    QuickBookTask::whereIn('id', $task_ids)->update(['status'=> QuickBookTask::STATUS_INPROGRESS, 'queued_at' => Carbon::now()->toDateTimeString()]);
                }
            });

            foreach($tasks as $task){
                $this->enqueueTask($task);
            }

        } catch (Exception $e) {

            Log::error($e);

        }
    }

    public function pollTasks($limit){
        $task = QuickBookTask::where('status', QuickBookTask::STATUS_PENDING)
                ->whereIn('company_id', function ($query) {
                    $query->select('company_id')
                        ->from('subscriptions')
                        ->where('status', Subscription::ACTIVE);
                })
                ->orderBy('parent_id', 'asc')->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->limit($limit)
                ->get();

        return $task;
    }

    public function enqueueTask($task){
        try {
            if (!QuickBooks::setCompanyScope(null, $task->company_id)) {
                throw new Exception("Unable to set company scope", 1);
            }
    
            $data = [
                'user_id' => Auth::user()->id,
                'payload' => $task,
            ];
    
            if($task->origin == QuickBookTask::ORIGIN_JP){
                $handler = $this->resolveJpHandler($task);
            }else{
                $handler = $this->resolveQBOHandler($task);
            }
    
            Log::info(sprintf("task %s is queued using %s handler", $task->id, $handler));
            Queue::connection('qbo')->push($handler, $data);
            
        } catch (Exception $e) {          
                $task->status = QuickBookTask::STATUS_ERROR;
                $task->msg = $e->getMessage();
                $task->save();                
        }
        
       

    }

    public function resolveJpHandler($task){
            

            // handle handler for new system
            $handler = '\App\Services\QuickBooks\QueueHandler\JP\\'. $task->object . '\\' .$task->action.'Handler';
            if (class_exists($handler)) {
                return $handler;
            }

            $jobHandler = '\App\Services\QuickBooks\QueueHandler\QB';
            $jobMapHandler = '\App\Services\QuickBooks\QueueHandler\Map\JP';

           if ($task->action == QuickBookTask::DELETE_FINANCIAL && $task->object == QuickBookTask::JOB) {
                
                return $jobMapHandler . '\DeleteFinancial\JobHandler@' . 'handle';      
            
            } else {

                $handler = $jobHandler . '\\' . $task->object . 'Handler';

                $action = $task->action;

                if (class_exists($handler)) {

                    $class = app()->make($handler);

                    if (method_exists($class, $action)) {

                        return $handler . '@' . $action;
                    }
                }

                throw new \Exception("Handler not found");
                
            }
    }

    public function resolveQBOHandler($task){
        $entry = $task->payload;
		$operation = $task->action;
		$object = $task->object;

        // handle handler for new system
        $handler = '\App\Services\QuickBooks\QueueHandler\QB\\'. $task->object . '\\' .$task->action.'Handler';
        if (class_exists($handler)) {
            return $handler;
        }

		if(($object != QuickBookTask::DUMP_QBO_CUSTOMER) && isset($entry['operation'])) {
			
			$operation = $entry['operation'];
		}

		$jobHandler = "App\Services\QuickBooks\QueueHandler\QB\\". $object . 'Handler';
		if($operation == QuickBookTask::DELETE_FINANCIAL && $object == QuickBookTask::CUSTOMER){
			$jobHandler = "App\Services\QuickBooks\QueueHandler\Map\QB\DeleteFinancial\\". $object . 'Handler';
			$operation = 'handle';
		}
		$operation = strtolower($operation);

		$data = [
			'user_id' => Auth::user()->id,
			'payload' => $task,
		];

		if(!class_exists($jobHandler)) {

			throw new Exception("Handler doesn't exists");
		}

		$class = app()->make($jobHandler);

		$handler = $jobHandler . '@' . $operation;

		if(!method_exists($class, $operation)) {
			throw new Exception("Handler method doesn't exists");
		}
        return $handler;
    }
}