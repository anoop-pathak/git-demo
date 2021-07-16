<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterActivityLogsAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('activity_logs', function($table)
		{
			if (!isIndexExists('activity_logs', 'activity_logs_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('activity_logs', 'activity_logs_job_id_index')) {
				
				$table->index('job_id');
			}

			if (!isIndexExists('activity_logs', 'activity_logs_created_by_index')) {
				
				$table->index('created_by');
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
		Schema::table('activity_logs', function($table) {
			$table->dropIndex('activity_logs_company_id_index');
			$table->dropIndex('activity_logs_job_id_index');
			$table->dropIndex('activity_logs_created_by_index');
		});
	}

}
