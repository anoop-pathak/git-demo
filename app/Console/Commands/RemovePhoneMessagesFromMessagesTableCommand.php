<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\PhoneMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class RemovePhoneMessagesFromMessagesTableCommand extends Command {

	protected $resourceService;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move-phone-messages-to-messages-table:rollback';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Remove Text messages from messages table.';

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
			$this->deletePhoneMessages($items);

			$processed += $items->count();
			$this->info("Processed data is " . $processed. " / " . $total);
		});

		$this->info("Script executed successfully.");
	}

	public function deletePhoneMessages($items)
	{
		foreach ($items as $key => $item) {
			try {

				if (!$item->message_id && !$item->message_thread_id) {
					continue;
				}

				$message = $item->message;
				$thread  = $item->messageThread;

				$message->delete();

				$item->message_id = null;
				$item->message_thread_id = null;
				$item->save();

				$otherMessage = Message::where('thread_id', '=', $thread->id)->exists();
				if ($otherMessage) {
					continue;
				}

				$participients = DB::table('message_thread_participants')->where('thread_id', $thread->id)->delete();
				$thread->delete();

			} catch (Exception $e) {
				Log::error($e);
			}

		}
	}
}
