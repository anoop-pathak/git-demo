<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MessageThread;
use App\Services\Messages\MessageService;
use App\Models\Task;

class createJobSpecificThread extends Command {

	protected $resourceService;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_job_specific_thread';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'create job specific separate thread and save job related message in that thread';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->service = app(MessageService::class);

		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$processed = 0;
		$builder = new Task;
		$totalTasks = $builder->whereNotNull('message_id')->whereNotNull('thread_id')->with(['message', 'participants']);
		$total = $totalTasks->count();
		$this->info("Total Items are: " . $total);
		$totalTasks->chunk(100, function($tasks) use ($total, &$processed) {
			$this->saveMessageInSeparateThread($tasks);

			$processed += $tasks->count();
			$this->info("Processed data is " . $processed. " / " . $total);
		});

		$this->info("Script executed successfully.");
	}

	public function saveMessageInSeparateThread($tasks)
	{
		foreach ($tasks as $task) {
			try {
				setAuthAndScope($task->created_by);

				$message = $task->message;
				$messageThread = $message->thread;

				$jobId = $task->job_id;
				$users = arry_fu($task->participants->lists('id'));
				$users[] = $task->created_by;
				$uniqueUser = arry_fu($users);
				sort($uniqueUser);

				$thread = null;

				if ((!is_null($message->job_id)) && (!is_null($messageThread->job_id)) && ($message->job_id != $messageThread->job_id)) {
					$thread = $this->checkOldExsistingThread(implode('_', $uniqueUser), $jobId);
					if (!$thread) {
						$thread = $this->service->createThread($uniqueUser, $jobId, null, null);
						$this->updateThreadCreatedAt($thread, $message);
					}

				} elseif ((!$messageThread->job_id) && (!is_null($message->job_id))) {
					$thread = $this->checkOldExsistingThread(implode('_', $uniqueUser), $jobId);
					if (!$thread) {
						$thread = $this->service->createThread($uniqueUser, $jobId, null, null);
						$this->updateThreadCreatedAt($thread, $message);
					}

				} elseif ((!$message->job_id && !is_null($messageThread->job_id)) || (!$messageThread->job_id && !$message->job_id)) {
					$thread = $this->checkOldExsistingThread(implode('_', $uniqueUser), $jobId);
					if (!$thread) {
						$thread = $this->service->getUserThread($uniqueUser);
						$this->updateThreadCreatedAt($thread, $message);
					}

				} else {
					continue;
				}

				$task->thread_id = $thread->id;
				$task->save();

				$message->thread_id = $thread->id;
				$message->save();

			} catch (\Exception $e) {
				\Log::error($e);
			}
		}
	}

	public function checkOldExsistingThread($participant, $jobId)
	{
		$thread = MessageThread::where('job_id', $jobId)->where('participant', $participant)->where('company_id', getScopeId())->first();

		return $thread;
	}

	public function updateThreadCreatedAt($thread, $message)
	{
		$thread->created_at = $message->created_at;
		$thread->updated_at = $message->created_at;

		$thread->save();
	}
}
