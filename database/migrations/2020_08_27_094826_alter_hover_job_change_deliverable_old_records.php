<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHoverJobChangeDeliverableOldRecords extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('UPDATE hover_jobs INNER JOIN jobs ON jobs.id = hover_jobs.job_id AND jobs.hover_deliverable_id IS NULL SET hover_jobs.deliverable_id = 3');
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
