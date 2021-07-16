<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MessageStatus;
use Carbon\Carbon;
use App\Services\Firebase\Firebase;

class FirebaseRemoveInvalidMessageCount extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:firebase_update_invalid_message_count';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update  messages count(which are not created actually) of users on firebase.';

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
		$start = Carbon::now()->toDateTimeString();
		$this->info("Command started at: {$start}");

		$invalidUnreadMessages = MessageStatus::whereIn('thread_id', function($query) {
				$query->select('id')
					->from('message_threads')
					->whereNotIn('id', function($query) {
						$query->select('thread_id')
						->from('messages');
					});
			})
			->join('message_threads', 'message_threads.id', '=', 'message_status.thread_id')
			->select('message_status.*', 'message_threads.company_id')
			->get();

		$totalMessages = $invalidUnreadMessages->count();
		$this->info("Total records: {$totalMessages}");

		foreach ($invalidUnreadMessages as $value) {
			setScopeId($value->company_id);
			Firebase::updateUserMessageCount($value->user_id);

			$this->info("Pending records: " . --$totalMessages);
		}

		MessageStatus::whereIn('id', $invalidUnreadMessages->pluck('id')->toArray())
			->update(['status' => 2]);

		$end = Carbon::now()->toDateTimeString();
		$this->info("Command completed at: {$end}");
	}
}