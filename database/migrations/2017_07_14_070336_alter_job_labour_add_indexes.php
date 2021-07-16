<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobLabourAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_labour', function($table)
		{
			if (!isIndexExists('job_labour','job_labour_job_id_index')) {
				
				$table->index('job_id');
			}

			if (!isIndexExists('job_labour','job_labour_labour_id_index')) {
				
				$table->index('labour_id');
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
		Schema::table('job_labour', function($table)
		{
			$table->dropIndex('job_labour_job_id_index');
			$table->dropIndex('job_labour_labour_id_index');
		});
	}

}
