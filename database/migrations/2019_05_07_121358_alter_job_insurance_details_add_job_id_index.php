<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInsuranceDetailsAddJobIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_insurance_details', function(Blueprint $table) {
			if (!isIndexExists('job_insurance_details', 'job_insurance_details_job_id_index')) {
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
		Schema::table('job_insurance_details', function(Blueprint $table) {
			$table->dropIndex('job_insurance_details_job_id_index');
		});
	}

}
