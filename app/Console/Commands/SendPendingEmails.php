<?php
namespace App\Console\Commands;

use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Carbon\Carbon;
use App\Models\Email;
use App\Models\User;
use Illuminate\Console\Command;
use App\Services\Emails\EmailServices;
use App;
use Illuminate\Support\Facades\Queue;
use App\Services\Emails\EmailQueueHandler;
use Settings;

class SendPendingEmails extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:send_pending_emails';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send Pending Emails.';

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
	 * When a command should run
	 *
	 * @param Scheduler $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	// public function schedule(Schedulable $scheduler)
	// {
	// 	return $scheduler->everyHours(2);
	// }

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$app = App::make(EmailServices::class);
		$lastTwoDays = Carbon::now()->subDays(2)->toDateString();
		$twoHours = Carbon::now()->subHours(4)->toDateTimeString();
		Email::where('status', Email::PENDING)
			->whereBetween('created_at', [$lastTwoDays, $twoHours])
			->with('recipientsTo', 'recipientsCc', 'recipientsBcc')
			->whereNull('bounce_notification_response')
			->chunk(100, function($emails) use($app){
				foreach ($emails as $email) {
					setScopeId($email->company_id);
					$files = [];
					foreach ($email->attachments as $attachment) {
						$files[] = [
							'path' => config('resources.BASE_PATH').$attachment->path,
							'name' => $attachment->name,
						];
					}
					$attachment['files'] = $files;
					$user = User::where('email', $email->from)->first();
					$replyToAddress = $app->getReplyToAddress($email);
					$data = [
		            	'user_id' 	=>	$user->id,
		            	'to'	  	=>	$email->recipientsTo->pluck('email')->toArray(),
		            	'cc'	  	=>	$email->recipientsCc->pluck('email')->toArray(),
		            	'bcc'	  	=>	$email->recipientsBcc->pluck('email')->toArray(),
		            	'reply_to'	=>	$replyToAddress,
		            	'email_id'	=> 	$email->id,
		            	'website_link' => Settings::get('WEBSITE_LINK'),
		            	'template' => null,
		            	'job_id'   => $email->job_id,
		            	'customer_id' => $email->customer_id,
		            	'files' => $files
					];
					Queue::push(EmailQueueHandler::class, $data);
				}
			});
	}
}
