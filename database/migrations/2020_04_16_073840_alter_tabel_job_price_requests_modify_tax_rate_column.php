<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTabelJobPriceRequestsModifyTaxRateColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE job_price_requests MODIFY COLUMN tax_rate FLOAT(9,3)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE job_price_requests MODIFY COLUMN tax_rate FLOAT(9,3)");
	}

}
