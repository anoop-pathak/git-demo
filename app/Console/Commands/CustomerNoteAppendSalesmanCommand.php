<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
class CustomerNoteAppendSalesmanCommand extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:append_salesman_customer_note';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Append salesman to customer note.';
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
		$filename = storage_path().'/data/Market_Sharp_Customers.csv';
		$excel = \App::make('excel');
	    $import = $excel->load($filename);
	    $fileCustomers = $import->get();
 	    foreach ($fileCustomers as $fileCustomer) {
	    	if(!$fileCustomer->salesperson)	 continue;
 	    	$phoneNumber = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace('-', '', $fileCustomer->phone)); // Removes special chars.
			$customer = Customer::join('phones', 'phones.customer_id', '=', 'customers.id')
					->where('company_id', 635)
				    ->where(function($query) use($fileCustomer, $phoneNumber){
				    	$query->where('phones.number', $phoneNumber)
				    		->orWhere('email', $fileCustomer->e_mail);
				    })->first();
			if(!$customer) continue;
 			$note = 'Salesman / Customer Rep: '. $fileCustomer->salesperson;
 			if($customer->note) {
				$note .= PHP_EOL . PHP_EOL. $customer->note;
			}
 			DB::table('customers')->where('id', $customer->id)->update(['note' => $note]);
	    }
	}
}