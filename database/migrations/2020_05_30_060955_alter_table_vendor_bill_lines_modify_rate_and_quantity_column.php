<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVendorBillLinesModifyRateAndQuantityColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE vendor_bill_lines MODIFY COLUMN rate	DECIMAL(16,2)");
		DB::statement("ALTER TABLE vendor_bill_lines MODIFY COLUMN quantity	DECIMAL(16,2)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE vendor_bill_lines MODIFY COLUMN rate	Float(10,2)");
		DB::statement("ALTER TABLE vendor_bill_lines MODIFY COLUMN quantity	Float(10,2)");
	}

}
