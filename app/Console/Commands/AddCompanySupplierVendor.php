<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;
use App\Models\Vendor;

class AddCompanySupplierVendor extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_company_supplier_vendors';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add company customer supplier as vendor.';

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
		$totalSupplier = Supplier::whereNotNull('company_id')
			->whereNotIn('id', function($query){
				$query->select('ref_id')
					->from('vendors')
					->where('type_id', 3)
					->whereNotNull('ref_id');
			})->count();
		$this->info('Pending Supplier - '. $totalSupplier);
		Supplier::whereNotNull('company_id')
		    ->with(['company.subscriber' => function($query){
		    	$query->withTrashed();
		    }])->whereNotIn('id', function($query){
				$query->select('ref_id')
					->from('vendors')
					->where('type_id', 3)
					->whereNotNull('ref_id');
			})->chunk(1000, function($suppliers) use (&$totalSupplier) {
				foreach($suppliers as $key => $supplier){
					$company = $supplier->company;
					if(!$company) continue;
					$ownerAdmin = $company->subscriber;

					$data = [
						'company_id'	=> $supplier->company_id,
						'first_name'	=>null,
						'last_name'		=> null,
						'display_name'	=> $supplier->name,
						'type_id'		=> 3,
						'address_id'	=> null,
						'created_by'	=> $ownerAdmin->id,
						'updated_by'	=> $ownerAdmin->id,
						'ref_id'		=> $supplier->id,
					];
					Vendor::create($data);
					--$totalSupplier;
					$this->info('Pending Sub - '. $totalSupplier);
				}
		});

		$totalSupplier = Supplier::whereNotNull('company_id')
			->whereNotIn('id', function($query){
				$query->select('ref_id')
					->from('vendors')
					->where('type_id', 3)
					->whereNotNull('ref_id');
			})->count();

		$this->info('Pending Sub - '. $totalSupplier);
	}
}
