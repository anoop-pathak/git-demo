<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPaymentNumbersAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_payment_numbers', function(Blueprint $table)
		{
			$table->index('company_id');;
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_payment_numbers', function(Blueprint $table)
		{
			$table->dropIndex('job_payment_numbers_company_id_index');
		});
	}

}
