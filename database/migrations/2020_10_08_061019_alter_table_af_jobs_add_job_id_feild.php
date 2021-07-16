<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAfJobsAddJobIdFeild extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('af_jobs', function(Blueprint $table)
		{
			$table->integer('job_id')->index()->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('af_jobs', function($table){
			$table->dropColumn('job_id');
		});
	}

}
