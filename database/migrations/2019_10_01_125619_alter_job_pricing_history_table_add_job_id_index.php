<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPricingHistoryTableAddJobIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_pricing_history', function(Blueprint $table) {
			$table->index('job_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_pricing_history', function(Blueprint $table) {
			$table->dropIndex('job_pricing_history_job_id_index');
		});
	}

}
