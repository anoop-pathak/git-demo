<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('worksheets', function($table)
		{
			if (!isIndexExists('worksheets', 'worksheets_job_id_index')) {
				
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
		Schema::table('worksheets', function($table)
		{
			$table->dropIndex('worksheets_job_id_index');
		});
	}

}
