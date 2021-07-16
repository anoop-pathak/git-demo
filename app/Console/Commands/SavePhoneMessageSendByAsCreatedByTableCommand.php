<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PhoneMessage;
use Exception;
use Illuminate\Support\Facades\Log;

class SavePhoneMessageSendByAsCreatedByTableCommand extends Command {

	protected $resourceService;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:save_phone_message_send_by_as_created_by';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Save Phone Messages SendBy as a Message Thread CreatedBy';

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
		$processed = 0;
		$builder = new PhoneMessage;
		$builder->with(['message', 'messageThread']);
		$total = $builder->count();
		$this->info("Total Items are: " . $total);
		$builder->chunk(100, function($items) use ($total, &$processed) {
			$this->saveMessageThreadCreatedBy($items);

			$processed += $items->count();
			$this->info("Processed data is " . $processed. " / " . $total);
		});

		$this->info("Script executed successfully.");
	}

	public function saveMessageThreadCreatedBy($items)
	{
		foreach ($items as $key => $item) {
			try {

				$message = $item->message;
				$phoneMessageThread = $item->messageThread;

				if (!$phoneMessageThread) {
					$item->message_thread_id = $message->thread_id;
					$item->save();
				}

				$thread = $item->messageThread;
				$thread->created_by = $item->send_by;
				$thread->save();

			} catch (Exception $e) {
				Log::error($e);
			}

		}
	}
}
