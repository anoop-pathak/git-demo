<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Supplier;
use App\Models\FinancialDetail;
use App\Models\CompanySupplier;
use Illuminate\Support\Facades\DB;

class SRSWorksheetsMarkAsOld extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
    protected $name = 'command:srs_worksheets_mark_as_old';

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
	public function fire()
	{
		$this->info('Command starting at - '. Carbon::now()->toDateTimeString());

        $srs = Supplier::srs();
		$companyIds = CompanySupplier::where('supplier_id', $srs->id)->pluck('company_id')->toArray();

        foreach ($companyIds as $companyId) {
			$this->markWorksheetAsOld($companyId, $srs->id);
		}

        $this->info('Command ending at - '. Carbon::now()->toDateTimeString());
	}

    private function markWorksheetAsOld($companyId, $srsId)
	{
		$worksheetIds = FinancialDetail::where('financial_details.company_id', $companyId)
			->where('financial_details.supplier_id', $srsId)
			->where('financial_products.srs_old_product', 1)
			->join('financial_products', 'financial_products.id', '=', 'financial_details.product_id')
			->select('financial_details.worksheet_id')
            ->pluck('worksheet_id')
            ->toArray();

        if(!empty($worksheetIds)) {
			foreach (array_chunk($worksheetIds, 100) as $t) {
				DB::table('worksheets')->whereIn('id', $t)->update(['srs_old_worksheet' => true]);
            }
		}
	}
}