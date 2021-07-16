<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MoveJobLabourToJobSubcontractor extends Command {
 	protected $resourceService;
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move_job_labour_to_job_sub';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Move job labour to job sub-contractors.';
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
	public function handle()
	{
		$jobSub = [];
 		DB::beginTransaction();
		try {
 			$jobLabour = DB::table('job_labour')->get();
 			foreach ($jobLabour as $key => $labour) {
				$jobSub[] = [
					'job_id' 			=> $labour->job_id,
					'sub_contractor_id' => $labour->labour_id,
					'work_crew_note_id' => $labour->work_crew_note_id,
					'schedule_id' 		=> $labour->schedule_id,
				];
			}
 			if(empty($jobSub)) return;
 			DB::table('job_sub_contractor')->insert($jobSub);
		} catch (Exception $e) {
			DB::rollBack();
			throw $e;
		}
 		DB::commit();
		$this->info('Labour moved successfully.');
	}
}