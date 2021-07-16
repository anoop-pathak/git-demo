<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class UpdateCustomerCanvassAndCallRepCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_customers_canvass_command';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update Customer Canvasser and Call Rep';

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
		$start = Carbon::now()->toDateTimeString();
		$totalConvassersCount = Customer::where(function($query){
			$query->where('call_center_rep', '!=', '')
				->orWhere('canvasser', '!=', '');
		})->where('updated_at', '<', '2020-12-30 6:00:00')
		  ->where('company_id', '!=', 12)
		  ->count();
		$this->info("Command Started At {$start}");
		$this->info("Total Records: ". $totalConvassersCount);
			$customers = Customer::where(function($query){
			$query->where('call_center_rep', '!=', '')
				->orWhere('canvasser', '!=', '');
			})->where('updated_at', '<', '2020-12-30 6:00:00')
			  ->where('company_id', '!=', 12)
			  ->chunk(100, function($customers) use (&$totalConvassersCount, $start){
				foreach ($customers as $key => $customer) {
					$callCenterRep = "Call Center Rep - $customer->call_center_rep";
					$canvasser = "Canvasser - $customer->canvasser";
					$messageContent = $customer->note;
					$noteUpdate = false;	
					if($customer->canvasser && strpos($customer->note, $canvasser) == false) {
						$noteUpdate = true;
						$messageContent .= "\nCanvasser - $customer->canvasser";
					}
					
					if($customer->call_center_rep && strpos($customer->note, $callCenterRep) == false) {
						$noteUpdate = true;
						$messageContent .= "\nCall Center Rep - $customer->call_center_rep";
					}

					if($noteUpdate) {
						$customer->note = $messageContent;

						DB::table('customers')->where('id', $customer->id)
							->update([
								'note' => $messageContent,
								'updated_at' => $start
							]);
					}
					$this->info("Pending Records:".--$totalConvassersCount);
				}
			});

		$end = Carbon::now()->toDateTimeString();
		$this->info("Command Completed At {$end}");

    }
}
