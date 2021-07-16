<?php namespace App\Console\Commands;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\FinancialProduct;
use App\Models\Supplier;
use App\Models\Company;
use App\Models\FinancialDetail;

class SRSFinancialDetailsCopyBranchCode extends Command {

 	/**
	 * The console command name.
	 *
	 * @var string
	 */
 	protected $name = 'command:srs_financial_details_compy_branch_code';

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
 		$srs = Supplier::srs();
 		if($srs) {
 			Company::chunk(50, function($companies) use($srs) {
 				foreach ($companies as $key => $company) {
 					FinancialDetail::where('supplier_id', $srs->id)
 					->where('company_id', $company->id)
 					->chunk(50, function($details) use($srs) {
 						$this->copyBranchCode($details, $srs);
 					});
 				}
 			});
 		}
 	}

 	private function copyBranchCode($details, $srs)
 	{
 		foreach ($details as $key => $detail) {
 			$product = FinancialProduct::where('company_id', $detail->company_id)
 			->where('supplier_id', $srs->id)
 			->where('code', $detail->product_code)
 			->first();
 			if(!$product) continue;
 			$detail->branch_code = $product->branch_code;
 			$detail->save();
 		}
 	}
 }