<?php
namespace App\Models;

class QuickbooksActivity extends BaseModel
{
	const SUCCESS  = 'success';
	const ERROR  = 'error';

	protected $table = 'quickbooks_activity';

	protected $fillable = [
		'company_id', 'customer_id', 'task_id', 'msg' , 'activity_type'
	];	

	/***** Protected Section *****/

	public function task() {
        return $this->belongsTo('QuickBookTask', 'task_id');
    }

    public function customer() {
        return $this->belongsTo('Customer', 'customer_id');
	}
	
	public static function record($msg, $customer_id = null, $task = null){
		$log = new self();

		if($customer_id){
			$log->customer_id = $customer_id;
		}else{
			$log->customer_id = $customer_id;
		}

		if($task){
			$log->company_id = $task->company_id;
			$log->task_id = $task->id;
		}else{
			$log->company_id = getScopeId();
		}

		$log->activity_type = strtolower($task->status);
		$log->msg = $msg;
		$log->save();

		return $log;
	}
}