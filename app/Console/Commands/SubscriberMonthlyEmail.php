<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Proposal;
use App\Models\Task;
use App\Models\Message;
use App\Models\Email;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\JobAwardedStage;
use Carbon\Carbon;
use Config;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Illuminate\Support\Facades\DB;
use App\Models\JobPayment;
use App\Models\User;
use App\Models\Appointment;

class SubscriberMonthlyEmail extends Command{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:subscriber_monthly_email_send';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send a monthly email to subscriber.';
 	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->monthlyAutoMail = false;
	}

 	/**
	 * When a command should run
	 *
	 * @param Scheduler $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{
		$this->monthlyAutoMail = true;
		return $scheduler->setSchedule(00, 12, 01, Scheduler::ANY, Scheduler::ANY);
	}

 	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		switchDBConnection('mysql2');
		$startMonth = Carbon::now()->subMonth()->startOfMonth();
		$endMonth = Carbon::now()->subMonth()->endOfMonth();

		$companyId = null;
		if(!$this->monthlyAutoMail){
			$companyId = $this->ask("Please enter company id for which you want to send monthly email: ");
			$year = $this->ask("Please enter year in which you want to send email: ");
			$month = $this->ask("Please enter month number (1-12) in which you want to send email: ");

			$startMonth = Carbon::parse($year .'-'.$month);
			$endMonth = Carbon::parse($year .'-'.$month)->endOfMonth();
		}

		$companies = Company::with('subscriber')
			->activated(Subscription::ACTIVE)
			->whereNull('companies.deleted_at');
		if($companyId){
			$companies = $companies->where('id', $companyId);
		}
		$companies = $companies->get();
 		$jobRepo = App::make('App\Repositories\JobRepository');

 		$totalCompanies = $companies->count();
		$this->info('Total Companies: '. $totalCompanies . PHP_EOL);
 		foreach ($companies as $key => $company) {
			setScopeId($company->id);
 			$awardedStage = JobAwardedStage::getJobAwardedStage($company->id);
			Config::set('awarded_stage', $awardedStage);
 			$jobs = $jobRepo->getJobsQueryBuilder();
 			$jobsAwarded = 0;
			if($awardedStage)	 {
				$jobAwardedQueryBuilder = clone $jobs;
				$jobAwardedQueryBuilder->closedJobs($startMonth, $endMonth);
				$jobsAwarded = $jobAwardedQueryBuilder->count();
			}
 			$jobLeadQueryBuilder = clone $jobs;
			$newJobs = $jobLeadQueryBuilder->whereBetween('created_at', [$startMonth,$endMonth])
				->count();
 			$jobs = generateQueryWithBindings($jobs);
 			$proposals = Proposal::whereBetween('proposals.created_at', [$startMonth, $endMonth])
				->where('proposals.status', '!=', Proposal::DRAFT)
				->where('proposals.company_id', '=', $company->id)
				->join(DB::raw("({$jobs}) as job"), 'proposals.job_id', '=', 'job.id')
				->whereNull('proposals.deleted_at')
				->count();
 				
 			//total received payment
			$receivedPaymentObj = JobPayment::whereBetween('job_payments.created_at', [$startMonth, $endMonth])
				->join(DB::raw("({$jobs}) as job"), 'job_payments.job_id', '=', 'job.id')
				->excludeCanceled()
				->whereNull('job_payments.ref_id')
				->sum('job_payments.payment');
 			$tasks = Task::whereBetween('tasks.created_at', [$startMonth, $endMonth])
				->where('tasks.company_id', '=', $company->id)
				->whereNull('tasks.deleted_at')
				->count();
 			$messages = Message::whereBetween('messages.created_at', [$startMonth, $endMonth])
				->where('messages.company_id', '=', $company->id)
				->whereNull('messages.deleted_at')
				->count();
 			$emails = Email::whereBetween('emails.created_at', [$startMonth, $endMonth])
				->where('emails.company_id', '=', $company->id)
				->whereNull('emails.deleted_at')
				->count();
 			$appointments = Appointment::whereBetween('appointments.created_at', [$startMonth, $endMonth])
				->recurring()
				->where('appointments.company_id', '=', $company->id)
				->whereNull('appointments.deleted_at')
				->whereNull('appointment_recurrings.deleted_at')
				->count();
 			$admins = User::whereBetween('created_at', [$startMonth, $endMonth])
				  ->where('users.group_id', '=', User::GROUP_ADMIN)
				  ->where('users.company_id', '=', $company->id)
				  ->whereNull('users.deleted_at')
				  ->count();
 			$standard = User::whereBetween('created_at', [$startMonth, $endMonth])
					->where('users.group_id', '=', User::GROUP_STANDARD_USER)
					->where('users.company_id', '=', $company->id)
					->whereNull('users.deleted_at')
					->count();
 			//sub contractors users
			$subContractor = User::whereBetween('created_at', [$startMonth, $endMonth])
					->where('users.group_id', '=', User::GROUP_SUB_CONTRACTOR_PRIME)
					->where('users.company_id', '=', $company->id)
					->whereNull('users.deleted_at')
					->count();

			//get subscriber's company name
			$companyName = $company->name;
 			$data = [
					'newJobs' 		   => $newJobs,
					'proposals' 	   => $proposals,
					'tasks'	 		   => $tasks,
					'messages' 		   => $messages,
					'emails' 		   => $emails,
					'appointments' 	   => $appointments,
					'admins' 		   => $admins,
					'standard' 		   => $standard,
					'subContractor'    => $subContractor,
					'received_payment' => $receivedPaymentObj,
					'jobsAwarded'	   => $jobsAwarded,
					'companyName'	   => $companyName,
 			];
 			$subscriber = $company->subscriber;
			if(!$subscriber) {
				$this->info('Subscriber not exist Company Id:'. $company->id . PHP_EOL);
				continue;
			}
 			$email = $subscriber->email;
			Mail::send('emails.monthly-email', ['data' => $data], function($message) use($company, $email) {
				$message->to($email)->subject('JobProgress Monthly Summary')->replyTo('info@jobprogress.com');
			});
			
			$this->info('Email send to: '. $email . ' Company Id:'. $company->id);
			$this->info('Pending Email: '. --$totalCompanies. PHP_EOL);
		}
 	}
}