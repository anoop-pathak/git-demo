<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PhoneMessage;
use Exception;
use Illuminate\Support\Facades\Log;

class UpdateMessageCreatedAtAndUpdatedAtCommand extends Command {

	protected $resourceService;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_created_at_and_updated_at';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update Created At And Updated At Value';

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
			$this->updateCreatedAtAndUpdatedAt($items);

			$processed += $items->count();
			$this->info("Processed data is " . $processed. " / " . $total);
		});

		$this->info("Script executed successfully.");
	}

	public function updateCreatedAtAndUpdatedAt($items)
	{
		foreach ($items as $key => $item) {
			try {

				$message = $item->message;
				$messageThread = $item->messageThread;

				$message->created_at = $item->created_at;
				$message->updated_at = $item->updated_at;
				$message->save();

				$messageThread->created_at = $item->created_at;
				$messageThread->updated_at = $item->updated_at;
				$messageThread->save();

			} catch (Exception $e) {
				Log::error($e);
			}

		}
	}
}
