<?php
namespace App\Services\QuickBooks\QueueHandler\QB\GhostJob;

use Exception;
use App\Services\QuickBooks\QueueHandler\QBBaseTaskHandler;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\QuickBooks;
use Illuminate\Support\Facades\Log;
use App\Repositories\JobRepository;

class CreateHandler extends QBBaseTaskHandler
{
    public function __construct(JobRepository $jobRepo) {
        $this->jobRepo = $jobRepo;
    }

	function getQboEntity($entityId)
    {
        return  QBCustomer::get($entityId);
    }

    function synch($task, $customer)
    {
        $customer = QuickBooks::toArray($customer['entity']);
        try {

            $ghostJob = QBCustomer::createGhostJob($customer['Id']);

            if($ghostJob && $ghostJob->customer_id){
                $this->jobRepo->qbGenerateJobNumber($ghostJob);
            }

            return $ghostJob;

        } catch(Exception $e) {

            throw $e;
        }
    }

    public function getErrorLogMessage(){
        $format = "%s %s failed to be %sd in JP";
        $message = sprintf($format, $this->task->object,  $this->task->object_id, $this->task->action);
        Log::info($message);
        return $message;

    }
}