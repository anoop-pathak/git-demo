<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFinancialCalculationAddCompanyIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table)
		{
			$table->integer('company_id')->index();
		});

		DB::statement("UPDATE job_financial_calculations INNER JOIN jobs ON jobs.id = job_financial_calculations.job_id SET job_financial_calculations.company_id = jobs.company_id");

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
			$table->dropColumn('company_id');
		});
	}

}
