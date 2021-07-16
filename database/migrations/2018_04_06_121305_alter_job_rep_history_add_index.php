<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobRepHistoryAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_rep_history', function($table)
		{
			if (!isIndexExists('job_rep_history', 'job_rep_history_rep_id_index')) {
				
				$table->index('rep_id');
			}

			if (!isIndexExists('job_rep_history', 'job_rep_history_job_id_index')) {
				
				$table->index('job_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_rep_history', function($table)
		{
			$table->dropIndex('job_rep_history_job_id_index');
			$table->dropIndex('job_rep_history_rep_id_index');
		});
	}

}
