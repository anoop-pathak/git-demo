<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobMetaAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_meta', function($table)
		{
			if (!isIndexExists('job_meta', 'job_meta_job_id_index')) {
				
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
		Schema::table('job_meta', function($table)
		{
			$table->dropIndex('job_meta_job_id_index');
		});
	}

}
