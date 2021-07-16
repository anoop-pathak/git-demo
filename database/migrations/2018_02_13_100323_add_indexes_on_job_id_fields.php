<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesOnJobIdFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_schedules', function($table)
		{
			if (!isIndexExists('job_schedules', 'job_schedules_job_id_index')) {
				
				$table->index('job_id');
			}
		});

		Schema::table('job_financial', function($table)
		{
			// $table->foreign('job_id')->references('id')->on('jobs');
			if (!isIndexExists('job_financial', 'job_financial_job_id_index')) {
				
				$table->index('job_id');
			}
		});

		Schema::table('addresses', function($table)
		{
			if (!isIndexExists('addresses', 'addresses_company_id_index')) {
				
				$table->index('company_id');
			}
		});

		// Schema::table('addresses', function($table)
		// {
		// 	$table->foreign('company_id')->references('id')->on('companies');
		// });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_schedules', function($table)
		{
			$table->dropIndex('job_schedules_job_id_index');
		});

		Schema::table('job_financial', function($table)
		{
			$table->dropIndex('job_financial_job_id_index');
		});

		Schema::table('addresses', function($table)
		{
			$table->dropIndex('addresses_company_id_index');
		});

		// Schema::table('addresses', function($table)
		// {
		// 	$table->dropForeign('addresses_company_id_foreign');
		// });
	}

}
