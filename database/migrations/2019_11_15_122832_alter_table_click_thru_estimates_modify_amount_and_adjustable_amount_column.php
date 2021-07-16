<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableClickThruEstimatesModifyAmountAndAdjustableAmountColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("ALTER TABLE click_thru_estimates MODIFY amount DECIMAL(16,2)");
		DB::statement("ALTER TABLE click_thru_estimates MODIFY adjustable_amount DECIMAL(16,2)");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("ALTER TABLE click_thru_estimates MODIFY amount DECIMAL(8,2)");
		DB::statement("ALTER TABLE click_thru_estimates MODIFY adjustable_amount DECIMAL(8,2)");
	}

}
