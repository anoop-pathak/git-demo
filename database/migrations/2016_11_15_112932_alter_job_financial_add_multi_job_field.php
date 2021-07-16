<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobFinancialAddMultiJobField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_financial_calculations', function($table){
			$table->boolean('multi_job')->default(false)->after('job_id');
			$table->boolean('multi_job_sum')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_financial_calculations', function($table){
			$table->dropColumn('multi_job');
			$table->dropColumn('multi_job_sum');
		});
	}

}
