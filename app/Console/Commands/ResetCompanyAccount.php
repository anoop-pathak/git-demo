<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\MessageThread;
use App\Models\Message;
use App\Models\Email;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\AppointmentRecurring;
use App\Models\ActivityLog;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateRecipient;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\FinancialMacro;
use App\Models\JobAwardedStage;
use App\Models\ScheduleRecurring;
use App\Models\JobSchedule;
use App\Models\JobType;
use App\Models\ProductionBoard;
use App\Models\ProductionBoardColumn;
use App\Models\Referral;
use App\Models\Setting;
use App\Models\Snippet;
use App\Models\Template;
use App\Models\TimeLog;
use App\Models\WorkflowTaskList;
use App\Models\Customer;
use App\Models\Job;
use App\Models\Division;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Firebase;
use Solr;
use Exception;

class ResetCompanyAccount extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:reset_company_account';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Reset an account of a company by deleting all its entities.';

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
		EnterPassword : $password = $this->secret('What is the password?');
		if($password != config('jp.developer_secret')) {
			$this->error('Incorrect Password.');
			goto EnterPassword;
		}

		$companyId = $this->ask('Please enter company id of the account that you want to do reset: ');
		$deletedAt = $startedAt = Carbon::now()->toDateTimeString();

		$this->info("----- Command started at: $startedAt -----");

		$company = Company::findOrFail($companyId);
		$systemUser = $company->anonymous;

		setScopeId($companyId);
		Auth::login($systemUser);

		$deletedBy = $systemUser->id;
		$deletedAt = Carbon::now()->toDateTimeString();
		$deleteReason = 'Company data reset by command';

		DB::beginTransaction();

		try {
			ActivityLog::where('company_id', $companyId)
				->update([
					'public' => false,
				]);
			$this->info("----- Activity Logs Updated with public = 0 -----");

			AppointmentRecurring::join('appointments', 'appointments.id', '=', 'appointment_recurrings.appointment_id')
				->where('company_id', $companyId)
				->update([
					'appointment_recurrings.deleted_at' => $deletedAt,
					'appointment_recurrings.deleted_by' => $deletedBy,
				]);
			Appointment::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Appointments Deleted -----");

			Email::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
				]);
			$this->info("----- Emails Deleted -----");

			EmailTemplateRecipient::join('email_templates', 'email_template_recipient.email_template_id', '=', 'email_templates.id')
				->where('email_templates.company_id', $companyId)
				->delete();
			EmailTemplate::where('company_id', $companyId)
				->delete();

			$this->info("----- Email Templates Deleted -----");

			$categories = config('jp.finacial_categories');
			FinancialCategory::whereNotIn('name', $categories)
				->where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Financial Categories Deleted -----");

			FinancialProduct::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
				]);
			$this->info("----- Financial Products Deleted -----");

			FinancialMacro::where('company_id', $companyId)->delete();
			$this->info("----- Financial Macros Deleted -----");

			DB::table('macro_details')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Macro Details Deleted -----");

			JobAwardedStage::where('company_id', $companyId)->delete();
			$this->info("----- Job Awarded Stages Deleted -----");

			ScheduleRecurring::join('job_schedules', 'schedule_recurrings.schedule_id', '=', 'job_schedules.id')
				->where('company_id', $companyId)
				->update([
					'schedule_recurrings.deleted_at' => $deletedAt,
					'schedule_recurrings.deleted_by' => $deletedBy,
				]);
			JobSchedule::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Job Schedules Deleted -----");

			JobType::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
				]);
			$this->info("----- Work Types & Job Categories Deleted -----");

			Message::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Messages Deleted -----");

			MessageThread::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Message Threads Deleted -----");

			$allBoardIds = ProductionBoard::where('company_id', $companyId)->pluck('id')->toArray();

			DB::table('production_board_jobs')->whereIn('board_id', $allBoardIds)->delete();
			$this->info("----- Production Board Jobs Unlnked -----");

			ProductionBoard::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);

			$this->info("----- Production Boards Deleted -----");

			ProductionBoardColumn::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Production Board Columns Deleted -----");

			$this->createProductionBoardColumns($companyId);
			$this->info("----- New General Production Boards Created -----");

			Referral::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Referrals Deleted -----");

			Setting::where('company_id', $companyId)
				->whereNotIn('key', [
					'TIME_ZONE',
					'SETUP_WIZARD_STEPS',
					'SETUP_WIZARD',
				])
				->delete();
			$this->info("----- Settings Deleted -----");

			Snippet::where('company_id', $companyId)->delete();
			$this->info("----- Snippets Deleted -----");

			Task::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Tasks Deleted -----");

			Template::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
				]);
			$this->info("----- Proposals and Estimation Templates Deleted -----");

			TimeLog::where('company_id', $companyId)->delete();
			$this->info("----- Time Logs(clockin/clockout) Deleted -----");

			WorkflowTaskList::where('company_id', $companyId)->delete();
			$this->info("----- Workflow Task List Deleted -----");

			Job::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
					'delete_note' => $deleteReason
				]);
			$this->info("----- Jobs Deleted -----");

			Customer::where('company_id', $companyId)
				->update([
					'deleted_at' => $deletedAt,
					'deleted_by' => $deletedBy,
					'delete_note' => $deleteReason
				]);
			$this->info("----- Customers Deleted -----");

			Division::where('company_id', $companyId)->delete();
			$this->info("----- Division Deleted -----");

			Firebase::updateWorkflow();
			$this->info("----- Firebase Workflow Updated -----");

			Solr::allCustomersDelete($companyId);

			$this->info("----- Customer and Jobs deleted from SOLR -----");

			$this->updateNotificationAndFirebaseCounts($companyId);

		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}

		DB::commit();

		$completedAt = Carbon::now()->toDateTimeString();

		$this->info("----- Command completed at: $completedAt -----");
	}

	private function createProductionBoardColumns($companyId)
	{
		$pbConfig = config('jp.default_production_board');
		$pb = ProductionBoard::create([
			'company_id' => $companyId,
			'name'       => $pbConfig['name'],
			'created_by' => 1,
		]);

		foreach ($pbConfig['columns'] as $column) {
			ProductionBoardColumn::create([
				'company_id' => $companyId,
				'board_id'   => $pb->id,
				'name'       => $column,
				'default'    => true,
				'created_by' => 1,
			]);
		}
	}

	private function updateNotificationAndFirebaseCounts($companyId)
	{
		User::where('company_id', $companyId)
			->chunk(10, function($users) {
				$userIds = $users->pluck('id')->toArray();

				Notification::whereIn('sender_id', $userIds)->delete();

				$this->info("----- Notification Deleted. -----");

				foreach ($users as $user) {
					Firebase::updateUserTaskCount($user->id);
					Firebase::updateUserMessageCount($user->id);
					Firebase::updateUserNotificationCount($user);
					Firebase::updateUserEmailCount($user->id);
					Firebase::updateUserSettings($user);
					Firebase::updateTodayAppointment($user->id);
					Firebase::updateTodayTask($user->id);
					Firebase::updateUserPermissions($user->id);
					Firebase::updateUserUpcomingAppointments($user->id);
					Firebase::updateUserUpcomingTasks($user->id);
				}

				$this->info("----- All entity counts of all users are updated on Firebase. -----");
			});
	}

}
