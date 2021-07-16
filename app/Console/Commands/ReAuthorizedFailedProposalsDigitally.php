<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\QueueStatus;
use JobQueue;

class ReAuthorizedFailedProposalsDigitally extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:reauthorized_failed_proposals_digitally';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will be used to push failed proposals again for digital signatures.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$now = Carbon::now()->toDateTimeString();
		$this->info("Command started at: ".$now."\n");

		$failedQueues = QueueStatus::join('proposals', 'proposals.id', '=', 'queue_statuses.entity_id')
			->whereNotIn('entity_id', function($query) {
				$query->select('entity_id')
					->from('queue_statuses')
					->where('action', JobQueue::PROPOSAL_DIGITAL_SIGN)
					->whereIn('queue_statuses.status', [JobQueue::STATUS_COMPLETED, JobQueue::STATUS_QUEUED, JobQueue::STATUS_IN_PROCESS]);
			})
			->where('action', JobQueue::PROPOSAL_DIGITAL_SIGN)
			->where('queue_statuses.status', JobQueue::STATUS_FAILED)
			->select('queue_statuses.*')
			->groupBy('queue_statuses.entity_id');

		$totalQueues = $failedQueues->count();

		$this->info("Total queues: ". $totalQueues);

		$failedQueues->chunk(100, function($queues) use(&$totalQueues) {
			foreach ($queues as $queue) {
				$data = $queue->data;
				$data['stop_notifications'] = true;

				JobQueue::enqueue(
					JobQueue::PROPOSAL_DIGITAL_SIGN,
					$queue->company_id,
					$queue->entity_id,
					$data
				);
				$this->info("Pending queues: ". --$totalQueues);
			}
		});

		$now = Carbon::now()->toDateTimeString();
		$this->info("Command completed at: ".$now);
	}

}
