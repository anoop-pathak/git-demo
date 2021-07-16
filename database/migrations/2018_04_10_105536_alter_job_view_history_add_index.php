<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobViewHistoryAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_view_history', function($table){
			$table->index('job_id');
			$table->index('user_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_view_history', function($table){
			$table->dropIndex('job_view_history_job_id_index');
			$table->dropIndex('job_view_history_user_id_index');
		});
	}

}
