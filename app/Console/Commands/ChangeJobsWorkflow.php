<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\User;
use App\Models\Workflow;
use App\Models\Job;
use App\Models\WorkflowStage;
use App\Models\JobWorkflowHistory;
use Illuminate\Support\Facades\DB;

class ChangeJobsWorkflow extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:change_jobs_workflow';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Change old jobs workflow with new workflow ';

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
		$companyId =  $this->ask('Enter company id:');
		$company = Company::find($companyId);


		if(!$company){
			$this->info('Please enter valid company id.');
			return;
		}

		$userId =  $this->ask('Enter user id:');
		$user = User::where('company_id', $companyId)->find($userId);

		if(!$user){
			$this->info('Please enter valid user id.');
			return;
		}

		$workflowId = $this->ask('Enter new workflow id:');
		$workflow = Workflow::where('company_id', $companyId)->find($workflowId);

		if(!$workflow){
			$this->info('Please enter valid workflow id.');
			return;
		}

		$workflowStages = WorkflowStage::where('workflow_id', (array) $workflow->id)->pluck('code')->toArray();

		$this->info("Start Time: ".Carbon::now()->toDateTimeString());
		$totalJobs = Job::where('company_id', $company->id)
			->whereNotIn('workflow_id', (array) $workflow->id)
			->count();

		$jobs = Job::where('company_id', $company->id)->whereNotIn('workflow_id', (array) $workflow->id)->with('jobWorkflow', 'jobWorkflowHistory')->get();

		foreach ($jobs as $job) {

			$this->info('Pending Records: '. --$totalJobs . ' Job Id:'. $job->id);

			$jobWorkflow = $job->jobWorkflow;

			$currentStage = $jobWorkflow->current_stage;

			if(in_array($currentStage, $workflowStages)) {
				//to check job current index in new workflow
				$stageIndex = array_search($currentStage, $workflowStages);
				$newStages = array_slice($workflowStages, 0, $stageIndex);

				if(empty($newStages)){
					DB::table('jobs')->where('id', $job->id)->update(['workflow_id' => $workflow->id]);
					continue;
				}

				$workflowHistoryStages = $job->jobWorkflowHistory->pluck('stage')->toArray();

				//get new stages which are not saved in job workflow history
				$diffStages = array_diff($newStages, $workflowHistoryStages);

				$currentDateTime = Carbon::now()->toDateTimeString();

				$stagesPosition = WorkflowStage::where('workflow_id', $workflow->id)
						->pluck('code', 'position')->toArray();

				foreach ($diffStages as $diffStage) {

					$diffStagePosition = array_search($diffStage, $stagesPosition);

					$previousStageHistory = JobWorkflowHistory::where('stage', $stagesPosition[$diffStagePosition - 1])
						->where('company_id', $company->id)
						->where('job_id', $job->id)
						->first();

					$nextStageHistory = JobWorkflowHistory::where('stage', $stagesPosition[$diffStagePosition + 1])
						->where('company_id', $company->id)
						->where('job_id', $job->id)
						->first();

					$startDate = $jobWorkflow->stage_last_modified;
					$completedDate = $jobWorkflow->stage_last_modified;

					if($previousStageHistory) {
						$startDate = $previousStageHistory->completed_date;
					}

					if($nextStageHistory) {
						$completedDate = $nextStageHistory->start_date;
					}

					$workFlowHistory = [
						'job_id'		=> $job->id,
						'company_id'	=> $job->company_id,
						'stage'			=> $diffStage,
						'modified_by'	=> $user->id,
						'created_at'    => $currentDateTime,
						'updated_at'    => $currentDateTime,
						'start_date'    => $startDate,
						'completed_date'  => $completedDate,

					];

					JobWorkflowHistory::insert($workFlowHistory);
				}

				DB::table('jobs')->where('id', $job->id)
					->update(['workflow_id' => $workflow->id]);
			}

		}
		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}
}