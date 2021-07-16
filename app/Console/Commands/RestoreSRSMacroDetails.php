<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;
use App\Models\FinancialMacro;
use FlySystem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\MacroDetail;
use App\Models\FinancialProduct;
use Exception;

class RestoreSRSMacroDetails extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:restore_srs_macro_details';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will be used to restore the SRS macro details that gets delete during product catalog refresh.';

	private $csvData = [];
	private $deletedProductIds = [];

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->srs = Supplier::srs();

		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$forAll = $this->confirm("Do you want to update all macros? Y|N ");
		$macroIds = null;
		if(!$forAll) {
			$macroIds = $this->ask("Please enter comma separated macro ids.");
		}

		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: $startedAt\n");

		$query = FinancialMacro::with([
				'company',
				'details' => function($query) {
					$query->withTrashed();
				}
			])
			->where('financial_macros.type', FinancialMacro::TYPE_SRS)
			->join('macro_details', function($join) {
				$join->on('financial_macros.id', '=', 'macro_details.macro_link_id');
			})
			->join('financial_products', function($join) {
				$join->on('financial_products.id', '=', 'macro_details.product_id')
					->where('financial_products.supplier_id', '=', $this->srs->id);
			})
			->whereDate('financial_products.deleted_at', '>', '2020-09-13')
			->whereNotNull('financial_products.deleted_at');

		if($macroIds) {
			$macroIds = arry_fu(explode(',', $macroIds));
			$query->whereIn('financial_macros.macro_id', $macroIds);
		}

		$query->select('financial_macros.*')
			->groupBy('financial_macros.id');

		$totalMacros = count($query->pluck('id')->toArray());

		if(!is_null($macroIds) && $totalMacros != count($macroIds)) {
			$this->info("Macro id(s) are invalid.\n");

			return;
		}
		$this->info("Total macros will be updated: $totalMacros -----\n");
		$this->canceledMacros = [];
		$this->updatedMacroIds = [];

		$this->checkAndUpdateMacros($query);

		$completedAt = Carbon::now()->toDateTimeString();

		$this->info("----- Updated Macro IDS -----\n");
		$this->info(implode(",\n", $this->updatedMacroIds)."\n");

		$this->info("----- Canceled Macro IDS -----");
		$this->info(implode(",\n", $this->canceledMacros)."\n");

		$fileCompletedMessage = 'Command Completed';
		if(!empty($this->csvData)){
			$csv = "";
			$csv .= implode(",", array_keys($this->csvData[0])) . "\n";
			foreach ($this->csvData as $row) {
				$csv .= implode(",", array_values($row)) . "\n";
			}

			$timestamp = timestamp();

			$url = FlySystem::uploadPublicaly("public/srs_macros_restored_detail_$timestamp.csv", $csv);
			$fileCompletedMessage .= ' and file download URL is:' .$url;
		}
		$this->info($fileCompletedMessage);
	}

	private function checkAndUpdateMacros($query)
	{
		$query->whereNotIn('financial_products.id', $this->deletedProductIds);

		$queryClone = clone $query;

		$queryClone->chunk(50, function($macros) {
			foreach ($macros as $macro) {
				DB::beginTransaction();
				try {

					$data = $this->updateMacroDetailWithNewProduct($macro);

					$this->csvData[] = [
						'Subscriber Id'					=> $macro->company->id,
						'Company Name'					=> $macro->company->name,
						'Macro Name'					=> $macro->macro_name,
						'Macro Id'						=> $macro->macro_id,
						'Total Products'				=> $data['total_products'],
						'Existing Active Product Count'	=> $data['existing_active_product_count'],
						'Existing Active Product Codes'	=> $data['existing_active_product_codes'],
						'Deleted Product Count'			=> $data['deleted_product_count'],
						'Deleted Product Codes'			=> $data['deleted_product_codes'],
						'New Active Product Count'		=> $data['new_active_product_count'],
						'New Active Product Codes'		=> $data['new_active_product_codes'],
					];

					$this->updatedMacroIds[] = $macro->macro_id;
					DB::commit();
				} catch (Exception $e) {
					DB::rollBack();
					$errMsg = $e->getMessage();

					$this->canceledMacros[] = $macro->macro_id;

					$this->info("----- Macro not updated. Macro ID: $macro->macro_id -----\n");
					$this->info("----- Error Details: $errMsg. -----\n");
				}
			}
		});

		if(count($query->pluck('id')->toArray())) {
			$this->checkAndUpdateMacros($query);
		}
	}

	private function updateMacroDetailWithNewProduct($macro)
	{
		$products = $macro->details;
		$existingActiveProducts = [];
		$deletedProducts = [];
		$newActiveProducts = [];
		foreach ($products as $product) {
			if(!$product->deleted_at) {
				$existingActiveProducts[] = $product->code;
				continue;
			}

			$newProduct = FinancialProduct::where('company_id', $macro->company_id)
				->where('supplier_id', $this->srs->id)
				->where('branch_code', $macro->branch_code)
				->where('code', $product->code)
				->orderBy('id', 'desc')
				->first();

			if(!$newProduct){

				$this->deletedProductIds[] = $product->id;
				$deletedProducts[] = $product->code;
				continue;
			}

			MacroDetail::where('company_id', $macro->company_id)
				->where('macro_link_id', $macro->id)
				->where('product_id', $product->id)
				->update([
					'product_id' => $newProduct->id
				]);

			$newActiveProducts[] = $product->code;

		}
		$data = [
			'total_products'				=> $products->count(),
			'existing_active_product_count'	=> count($existingActiveProducts),
			'existing_active_product_codes'	=> implode('; ', $existingActiveProducts),
			'deleted_product_count'			=> count($deletedProducts),
			'deleted_product_codes'			=> implode('; ', $deletedProducts),
			'new_active_product_count'		=> count($newActiveProducts),
			'new_active_product_codes'		=> implode('; ', $newActiveProducts),
		];

		return $data;
	}

}
