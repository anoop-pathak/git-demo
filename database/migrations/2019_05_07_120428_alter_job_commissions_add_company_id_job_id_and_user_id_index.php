<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobCommissionsAddCompanyIdJobIdAndUserIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_commissions', function(Blueprint $table) {
			if (!isIndexExists('job_commissions', 'job_commissions_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('job_commissions', 'job_commissions_job_id_index')) {
				$table->index('job_id');
			}
			if (!isIndexExists('job_commissions', 'job_commissions_user_id_index')) {
				$table->index('user_id');
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
		Schema::table('job_commissions', function(Blueprint $table) {
			$table->dropIndex('job_commissions_company_id_index');
			$table->dropIndex('job_commissions_job_id_index');
			$table->dropIndex('job_commissions_user_id_index');
		});
	}

}
