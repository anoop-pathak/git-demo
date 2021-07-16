<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsCopyDivisionIdToProjeectsFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("UPDATE jobs INNER JOIN (select id,division_id from jobs where multi_job = true) parent_job ON parent_job.id = jobs.parent_id SET jobs.division_id = parent_job.division_id");
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
