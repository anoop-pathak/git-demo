<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\MessageThread;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\PhoneMessageRepository;
use App;

class PhoneMessagesMoveToMessagesTableCommand extends Command {

	protected $resourceService;

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move-phone-messages-to-messages-table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Move Text messages from phone messages table to messages table and maintain relationships.';

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
		$total = $builder->count();
		$this->info("Total Items are: " . $total);
		$pMessages = $builder->chunk(100, function($items) use ($total, &$processed) {
			$this->processPhoneMessages($items);

			$processed += $items->count();
			$this->info("Processed data is " . $processed. " / " . $total);
		});

		$this->info("Script executed successfully.");
	}

	public function processPhoneMessages($items)
	{
		foreach ($items as $key => $item) {
			try {
				if ($item->message_id && $item->message_thread_id) {
					continue;
				}

				DB::beginTransaction();
				$thread = $this->createMessageThread($item);
				$this->createMessages($thread->id, $item);
				DB::commit();
			} catch (Exception $e) {
				DB::rollback();
				Log::error($e);
			}

		}
	}

	private function createMessageThread($item)
	{
		$thread = MessageThread::where('id', $this->makeThreadId($item))->first();

		if($thread) {
			$item->message_thread_id = $thread->id;
			$item->save();

			return $thread;
		}
		$number = $item->to_number;
		$payload = [
			'id' => $this->makeThreadId($item),
			'company_id' => $item->company_id,
			'job_id' => $item->job_id,
			'type'   => MessageThread::TYPE_SMS,
			'phone_number' => $number,
			'participant'  => $item->send_by . '_' . $item->customer_id,
		];

		$thread = MessageThread::create($payload);
		$item->message_thread_id = $thread->id;
		$item->save();
		$this->threadParticipientsPayload($thread, $item);
		return $thread;
	}

	private function createMessages($threadId, $item)
	{
		$data = [
			'sender_id'   => $item->send_by,
			'company_id'  => $item->company_id,
			'subject'     => "",
			'content'     => $item->body,
			'sms_id'      => $item->sid,
			'sms_status'  => Message::getSMSStatus($item->status),
			'customer_id' => $item->customer_id,
			'thread_id'   => $threadId,
		];

		$msg = Message::create($data);
		$item->message_id = $msg->id;
		$item->save();
		return $msg;
	}

	/**
	 * Store participients with thread.
	 *
	 * @param MessageThread $thread
	 * @param Message $item
	 * @return void
	 */
	private function threadParticipientsPayload($thread, $item)
	{
		$participients = [
			[
				'thread_id' => $thread->id,
				'ref_type'  => MessageThread::USER_PARTICIPANTS,
				'ref_id'    => $item->send_by,
				'user_id'   => $item->send_by
			]
		];
		if($item->customer_id) {
			$participients[] = [
				'thread_id' => $thread->id,
				'ref_type'  => MessageThread::CUSTOMER_PARTICIPANTS,
				'ref_id'    => $item->customer_id,
				'user_id'   => $item->send_by
			];
		}

		foreach ($participients as $participient) {
			DB::table('message_thread_participants')->insert($participient);
		}

		// store participients which are saved with same phone number reference.
		$phoneMessagerepository = App::make(PhoneMessageRepository::class);
		$refParticipients = $phoneMessagerepository->getParticipants($thread->phone_number);
		foreach ($refParticipients as $refType => $patArr) {
            foreach ($patArr as $key2 => $refId) {

				if($refType == MessageThread::CUSTOMER_PARTICIPANTS && $item->customer_id == $refId) {
					continue;
				} else if ($refType == MessageThread::USER_PARTICIPANTS && $item->send_by == $refId) {
					continue;
				}
				if(!$refId) {
					continue;
				}

				$participantData = [
					'thread_id' => $thread->id,
					'ref_type'  => $refType,
					'ref_id'    => $refId,
					'user_id'   => $item->send_by
				];

				DB::table('message_thread_participants')->insert($participantData);
            }
        }
		return $participients;
	}

	/**
	 * Create thread id on the basis of from number + to number + company id
	 *
	 * @param MessageThread $item
	 * @return String
	 */
	private function makeThreadId($item)
	{
		return str_replace('+', '', $item->from_number ."_". $item->to_number . '_'. $item->company_id . '_'. $item->customer_id . '_'. $item->send_by);
	}
}
