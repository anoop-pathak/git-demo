<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class DisconnectSrsAccounts extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$srs = Supplier::srs();
		if($srs) {
			CompanySupplier::where('supplier_id', $srs->id)
			->whereNull('deleted_at')
			->update(['deleted_at' => Carbon::now()->toDateString()]);

			SrsShipToAddress::whereNull('deleted_at')
				->update(['deleted_at' => Carbon::now()->toDateString()]);
			
			SupplierBranch::whereNull('deleted_at')
				->update(['deleted_at' => Carbon::now()->toDateString()]);

			FinancialProduct::where('supplier_id', $srs->id)
				->whereNull('deleted_at')
				->update([
					'srs_old_product' => true,
					'deleted_at' => Carbon::now()->toDateString(),
				]);

			DB::table('ship_to_address_branches')->truncate();
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}

}
