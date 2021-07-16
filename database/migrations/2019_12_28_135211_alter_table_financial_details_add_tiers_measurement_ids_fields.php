<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialDetailsAddTiersMeasurementIdsFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->integer('tier1_measurement_id')->nullable();
			$table->integer('tier2_measurement_id')->nullable();
			$table->integer('tier3_measurement_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->dropColumn('tier1_measurement_id');
			$table->dropColumn('tier2_measurement_id');
			$table->dropColumn('tier3_measurement_id');
		});
	}

}
