<?php
namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\User;
use App\Models\ProductionBoard;
use App\Models\Notification;
use App\Models\Resource;
use App\Models\Workflow;
use App\Models\WorkflowStage;
use App\Models\QBDesktopUser;
use App\Models\ProductionBoardColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Firebase;
use Solr;

class DeleteAllCompanyAccountData extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete_all_company_account_data';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

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
		DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		DB::beginTransaction();

		try {
			DB::table('activity_logs')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Activity Logs Deleted -----");

			DB::table('appointment_recurrings')
				->join('appointments', 'appointments.id', '=', 'appointment_recurrings.appointment_id')
				->where('company_id', $companyId)
				->delete();

			DB::table('appointments')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Appointments Deleted -----");

			DB::table('company_contacts')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Company Contacts Deleted -----");

			DB::table('flags')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Customers and Jobs Flag Deleted -----");

			DB::table('emails')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Emails Deleted -----");

			DB::table('email_template_recipient')
				->join('email_templates', 'email_template_recipient.email_template_id', '=', 'email_templates.id')
				->where('company_id', $companyId)
				->delete();

			DB::table('email_templates')
				->where('company_id', $companyId)
				->delete();

			$this->info("----- Email Templates Deleted -----");

			$categories = config('jp.finacial_categories');
			DB::table('financial_categories')
				->whereNotIn('name', $categories)
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Financial Categories Deleted -----");

			DB::table('financial_products')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Financial Products Deleted -----");

			DB::table('financial_macros')
				->where('company_id', $companyId)
				->delete();

			$this->info("----- Financial Macros Deleted -----");

			DB::table('macro_details')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Macro Details Deleted -----");

			DB::table('job_awarded_stages')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Job Awarded Stages Deleted -----");

			DB::table('schedule_recurrings')
				->join('job_schedules', 'job_schedules.id', '=', 'schedule_recurrings.schedule_id')
				->where('company_id', $companyId)
				->delete();

			DB::table('job_schedules')
				->where('company_id', $companyId)
				->delete();

			$this->info("----- Job Schedules Deleted -----");

			DB::table('job_types')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Work Types & Job Categories Deleted -----");

			DB::table('messages')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Messages Deleted -----");

			DB::table('message_threads')
				->where('company_id', $companyId)
				->delete();

			$this->info("----- Message Threads Deleted -----");

			$allBoardIds = ProductionBoard::where('company_id', $companyId)->pluck('id')->toArray();

			DB::table('production_board_jobs')
				->whereIn('board_id', $allBoardIds)
				->delete();
			$this->info("----- Production Board Jobs Unlnked -----");

			DB::table('production_boards')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Production Boards Deleted -----");

			DB::table('production_board_columns')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Production Board Columns Deleted -----");

			$this->createProductionBoardColumns($companyId);
			$this->info("----- New General Production Boards Created -----");

			DB::table('referrals')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Referrals Deleted -----");

			DB::table('settings')
				->where('company_id', $companyId)
				->whereNotIn('key', [
					'TIME_ZONE',
					'SETUP_WIZARD_STEPS',
					'SETUP_WIZARD',
				])
				->delete();

			$this->info("----- Settings Deleted -----");


			DB::table('snippets')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Snippets Deleted -----");

			DB::table('tasks')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Tasks Deleted -----");

			DB::table('ev_orders')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Eagle View Orders Deleted -----");

			DB::table('sm_orders')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- SkyMeasure Orders Deleted -----");

			DB::table('templates')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Proposals and Estimation Templates Deleted -----");

			DB::table('timelogs')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Time Logs(clockin/clockout) Deleted -----");

			DB::table('workflow_task_lists')
				->where('company_id', $companyId)
				->delete();

			$this->info("----- Workflow Task List Deleted -----");

			DB::table('job_workflow')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Job Workflow Deleted -----");

			DB::table('job_workflow_history')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Job Workflow History Deleted -----");

			$this->setDefaultWorkflow($companyId, $deletedBy);
			$this->info("-----Set Default Workflow-----");

			$this->deleteQBDesktop($companyId);
			$this->info("-----Quickbook Desktop Data deleted-----");

			DB::table('jobs')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Jobs Deleted -----");

			DB::table('customers')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Customers Deleted -----");

			DB::table('divisions')
				->where('company_id', $companyId)
				->delete();
			$this->info("----- Division Deleted -----");

			Firebase::updateWorkflow();
			$this->info("----- Firebase Workflow Updated -----");

			Solr::allCustomersDelete($companyId);

			$this->info("----- Customer and Jobs deleted from SOLR -----");

			$this->updateNotificationAndFirebaseCounts($companyId);

		} catch (Exception $e) {
			DB::rollback();
			DB::statement('SET FOREIGN_KEY_CHECKS=1;');
			throw $e;
		}

		DB::commit();
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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

	private function setDefaultWorkflow($companyId, $deletedBy){
        	$firstWorkflow = Workflow::where('company_id', $companyId)->first();
        	$workflowIds  = Workflow::where('company_id', $companyId)->pluck('id')->toArray();

        	$remWorkflowIds = array_diff($workflowIds, (array)$firstWorkflow->id);

        	if(!empty($remWorkflowIds)){
	       		$resourcesIds = WorkflowStage::whereIn('workflow_id', (array)$remWorkflowIds)
	       			->pluck('resource_id')->toArray();

	       		$firstResourcesIds = WorkflowStage::where('workflow_id', $firstWorkflow->id)
				   ->pluck('resource_id')->toArray();

	       		$remResourcesIds = array_diff(arry_fu($resourcesIds), $firstResourcesIds);

	       		if(!empty($remResourcesIds)){
	       			Resource::whereIn('id', $remResourcesIds)
	       				->update([
						'deleted_at' => Carbon::now()->toDateTimeString(),
						'deleted_by' => $deletedBy,
					]);
	       		}

				DB::table('workflow')
					->whereIn('id', $remWorkflowIds)
					->delete();
				DB::table('workflow_stages')
					->whereIn('workflow_id', $remWorkflowIds)
					->delete();
        	}
	}

	private function deleteQBDesktop($companyId)
	{
		$qbUser = QBDesktopUser::where('company_id', $companyId)->first();

		if($qbUser){
			DB::table('quickbook_meta')->where('qb_desktop_username', $qbUser->qb_username)->delete();
			DB::table('quickbooks_queue')->where('qb_username', $qbUser->qb_username)->delete();
			DB::table('quickbooks_ticket')->where('qb_username', $qbUser->qb_username)->delete();
		}

		DB::table('quickbooks_user')->where('company_id', $companyId)->delete();
		DB::table('quickbooks_uom')->where('company_id', $companyId)->delete();
		DB::table('qbd_items')->where('company_id', $companyId)->delete();
		DB::table('qbd_item_sales_taxes')->where('company_id', $companyId)->delete();
		DB::table('qbd_item_sales_tax_groups')->where('company_id', $companyId)->delete();
		DB::table('qbd_sales_tax_codes')->where('company_id', $companyId)->delete();
		DB::table('qbd_tax_group_sales_tax')->where('company_id', $companyId)->delete();
		DB::table('qbd_units_of_measurement')->where('company_id', $companyId)->delete();
		DB::table('financial_categories')->where('company_id', $companyId)->update(['qb_desktop_id' => null]);
	}

}
