<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPaymentsAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
		Schema::table('job_payments', function($table)
		{
			if (!isIndexExists('job_payments', 'job_payments_job_id_index')) {
				
				$table->index('job_id');
			}

			if (!isIndexExists('job_payments', 'job_payments_customer_id_index')) {
				
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
		Schema::table('job_payments', function($table)
		{
			$table->dropIndex('job_payments_job_id_index');
			$table->dropIndex('job_payments_customer_id_index');
		});
	}

}
