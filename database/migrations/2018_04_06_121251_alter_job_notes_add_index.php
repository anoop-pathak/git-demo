<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobNotesAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_notes', function($table)
		{
			if (!isIndexExists('job_notes', 'job_notes_company_id_index')) {
				
				$table->index('company_id');
			}

			if (!isIndexExists('job_notes', 'job_notes_job_id_index')) {
				
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
		Schema::table('job_notes', function($table)
		{
			$table->dropIndex('job_notes_job_id_index');
			$table->dropIndex('job_notes_company_id_index');
		});
	}
}
