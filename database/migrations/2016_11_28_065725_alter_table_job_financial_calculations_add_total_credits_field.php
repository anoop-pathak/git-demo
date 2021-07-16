<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobFinancialCalculationsAddTotalCreditsField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->float('total_credits')->after('total_received_payemnt');
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
			$table->dropColumn('total_credits');
		});
	}

}
