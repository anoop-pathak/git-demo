<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RemoveAllClickThruSettings extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:remove_all_clickthru_settings';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Remove All ClickThru Settings';

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
		$this->info('Command Start Date:'. Carbon::now());

		DB::table('estimate_type_layers')->truncate();
		DB::table('waterproofing')->truncate();
		DB::table('estimate_levels')->truncate();
		DB::table('access_to_home')->truncate();
		DB::table('estimate_chimnies')->truncate();
		DB::table('estimate_gutters')->truncate();
		DB::table('estimate_structures')->truncate();
		DB::table('estimate_ventilations')->truncate();

		$this->info('Command Start Date:'. Carbon::now());
	}
}