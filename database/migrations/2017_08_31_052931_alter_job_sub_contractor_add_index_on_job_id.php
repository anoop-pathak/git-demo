<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobSubContractorAddIndexOnJobId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_sub_contractor', function($table)
		{
			if (!isIndexExists('job_sub_contractor','job_sub_contractor_job_id_index')) {
				
				$table->index('job_id');
			}

			if (!isIndexExists('job_sub_contractor','job_sub_contractor_sub_contractor_id_index')) {
				
				$table->index('sub_contractor_id');
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
		Schema::table('job_sub_contractor', function($table)
		{
			$table->dropIndex('job_sub_contractor_job_id_index');
			$table->dropIndex('job_sub_contractor_sub_contractor_id_index');
		});
	}

}
