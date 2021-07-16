<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaidCommissionInJobFinancialCalculations extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->decimal('paid_commission', 16,2)->after('total_commission')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->dropColumn('paid_commission');
		});
	}

}
