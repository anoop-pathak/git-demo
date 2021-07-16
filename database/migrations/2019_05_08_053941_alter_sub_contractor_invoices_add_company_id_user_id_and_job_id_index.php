<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubContractorInvoicesAddCompanyIdUserIdAndJobIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('sub_contractor_invoices', function(Blueprint $table) {
			if (!isIndexExists('sub_contractor_invoices', 'sub_contractor_invoices_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('sub_contractor_invoices', 'sub_contractor_invoices_user_id_index')) {
				$table->index('user_id');
			}
			if (!isIndexExists('sub_contractor_invoices', 'sub_contractor_invoices_job_id_index')) {
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
		Schema::table('sub_contractor_invoices', function(Blueprint $table) {
			$table->dropIndex('sub_contractor_invoices_company_id_index');
			$table->dropIndex('sub_contractor_invoices_user_id_index');
			$table->dropIndex('sub_contractor_invoices_job_id_index');
		});
	}

}
