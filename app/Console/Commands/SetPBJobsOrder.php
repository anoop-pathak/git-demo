<?php

namespace App\Console\Commands;

use App\Models\ProductionBoard;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SetPBJobsOrder extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:set_pb_jobs_order';
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
	public function handle()
	{
		$companyBoards = ProductionBoard::get()->groupBy('company_id');
 		foreach ($companyBoards as $boardList) {
			foreach ($boardList as $key => $board) {
				$this->setJobOrder($board);
			}
		}
 		$this->info('Order set successfully.');
	}
 	private function setJobOrder($board)
	{
		$jobs = DB::table('production_board_jobs')->where('board_id', $board->id)
			->orderBy('updated_at', 'asc')
			->whereNotNull('job_id')
			->get();
 		$order = 1;
 		foreach ($jobs as $key => $job) {
			DB::table('production_board_jobs')->where('board_id', $board->id)
				->where('id', $job->id)
				->update(['order' => $order]);
 			$order++;
		}
	}
}