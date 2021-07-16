<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Setting;


class UpdatePBAutoPostSettings extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_pb_auto_post_settings';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update structure of progress board auto post settings.';

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
		$settings = Setting::where('key', 'PB_AUTO_POST')->get();

		$totalSettings = $settings->count();

		$this->info("Start Time: ".Carbon::now()->toDateTimeString());

		foreach ($settings as $setting) {
			$this->info('Pending Records: '. --$totalSettings . ' Setting Id:'. $setting->id);

			$value = $setting->value;

			if(isset($value['stage']) || isset($value['board_ids'])){
				$arrValues[] = $value;
				$setting->value = $arrValues;
				$setting->save();
			}
		}
		$this->info("Start Time: ".Carbon::now()->toDateTimeString());
	}
}