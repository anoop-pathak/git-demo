<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function($table)
		{
			if (!isIndexExists('job_invoices', 'job_invoices_customer_id_index')) {
				
				$table->index('customer_id');
			}

			if (!isIndexExists('job_invoices', 'job_invoices_job_id_index')) {
				
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
		Schema::table('job_invoices', function($table)
		{
			$table->dropIndex('job_invoices_job_id_index');
			$table->dropIndex('job_invoices_customer_id_index');
		});
	}

}
