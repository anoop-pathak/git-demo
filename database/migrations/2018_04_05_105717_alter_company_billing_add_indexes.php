<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCompanyBillingAddIndexes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_billing', function($table)
		{
			if (!isIndexExists('company_billing', 'company_billing_company_id_index')) {
				
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
		Schema::table('company_billing', function($table)
		{
			$table->dropIndex('company_billing_company_id_index');
		});
	}

}
