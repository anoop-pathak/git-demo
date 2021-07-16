<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCustomerFeedbacksAddCompanyIdJobIdAndCustomerIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('customer_feedbacks', function(Blueprint $table) {
			if (!isIndexExists('customer_feedbacks', 'customer_feedbacks_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('customer_feedbacks', 'customer_feedbacks_job_id_index')) {
				$table->index('job_id');
			}
			if (!isIndexExists('customer_feedbacks', 'customer_feedbacks_customer_id_index')) {
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
		Schema::table('customer_feedbacks', function(Blueprint $table) {
			$table->dropIndex('customer_feedbacks_company_id_index');
			$table->dropIndex('customer_feedbacks_job_id_index');
			$table->dropIndex('customer_feedbacks_customer_id_index');
		});
	}

}
