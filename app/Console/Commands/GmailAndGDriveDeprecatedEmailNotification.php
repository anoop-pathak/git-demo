<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Mail;

class GmailAndGDriveDeprecatedEmailNotification extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:gmail_gdrive_deprecated_email_notification';

    /**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Gmail And GDrive Deprecated Email Notification';

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
		$users = User::whereIn('company_id', function($query){
			$query->select('company_id')->from('subscriptions')->where('status',  Subscription::ACTIVE);
		})->loggable()->active()->groupBy('email')->get();

        $this->info('Total Users: '. $totalUsers = $users->count());

        foreach ($users as $user) {
			Mail::send('emails.users.google_gmail_drive', [], function($message) use ($user) {
	            $message->to($user->email);
				$message->subject('JobProgress Alert! - Google Services (Gmail and Drive) are getting temporarily discontinued');
            });

            $this->info('Pending Users: '. --$totalUsers. ' Email Sent to: '. $user->email);
		}
	}
}