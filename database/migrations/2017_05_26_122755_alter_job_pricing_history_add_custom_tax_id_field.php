<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPricingHistoryAddCustomTaxIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_pricing_history', function(Blueprint $table)
		{
			$table->integer('custom_tax_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_pricing_history', function(Blueprint $table)
		{
			$table->dropColumn('custom_tax_id');
		});
	}

}
