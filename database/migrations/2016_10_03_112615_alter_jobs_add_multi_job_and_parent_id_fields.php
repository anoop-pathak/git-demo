<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsAddMultiJobAndParentIdFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs', function($table){
			$table->boolean('multi_job')->default(false)->after('company_id');
			$table->unsignedInteger('parent_id')->after('multi_job')->nullable();
        	$table->foreign('parent_id')->references('id')->on('jobs');
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
			$table->dropForeign('jobs_parent_id_foreign');
			$table->dropColumn('parent_id');
			$table->dropColumn('multi_job');
		});
	}

}
