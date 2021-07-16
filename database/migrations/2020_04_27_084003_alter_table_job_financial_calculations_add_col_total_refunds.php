<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobFinancialCalculationsAddColTotalRefunds extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->float('total_refunds')->nullable()->after('total_credits');
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
			$table->dropColumn('total_refunds');
		});
	}

}
