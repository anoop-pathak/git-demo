<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFinancialCalculationsAddIndexOnJobId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function($table)
		{
			if (!isIndexExists('job_financial_calculations', 'job_financial_calculations_job_id_index')) {
				
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
		Schema::table('job_financial_calculations', function($table)
		{
			$table->dropIndex('job_financial_calculations_job_id_index');
		});
	}

}
