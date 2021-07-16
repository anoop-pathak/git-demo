<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobCreditsAddCompanyIdJobIdAndCustomerIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_credits', function(Blueprint $table) {
			if (!isIndexExists('job_credits', 'job_credits_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('job_credits', 'job_credits_job_id_index')) {
				$table->index('job_id');
			}
			if (!isIndexExists('job_credits', 'job_credits_customer_id_index')) {
				$table->index('customer_id');
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
		Schema::table('job_credits', function(Blueprint $table) {
			$table->dropIndex('job_credits_company_id_index');
			$table->dropIndex('job_credits_job_id_index');
			$table->dropIndex('job_credits_customer_id_index');
		});
	}

}
