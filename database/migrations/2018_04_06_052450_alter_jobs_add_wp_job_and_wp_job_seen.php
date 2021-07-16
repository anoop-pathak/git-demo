<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsAddWpJobAndWpJobSeen extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs', function($table){
			$table->boolean('wp_job')->default(0);
			$table->boolean('wp_job_seen')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('jobs', function($table){
			$table->dropColumn('wp_job');
			$table->dropColumn('wp_job_seen');
		});
	}

}
