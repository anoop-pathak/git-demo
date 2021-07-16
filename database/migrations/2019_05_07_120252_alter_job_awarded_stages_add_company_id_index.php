<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobAwardedStagesAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_awarded_stages', function(Blueprint $table) {
			if (!isIndexExists('job_awarded_stages', 'job_awarded_stages_company_id_index')) {
				$table->index('company_id');
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
		Schema::table('job_awarded_stages', function(Blueprint $table) {
			$table->dropIndex('job_awarded_stages_company_id_index');
		});
	}

}
