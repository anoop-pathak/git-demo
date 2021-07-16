<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobCommissionsAddPaidMarkedAndPaidByField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_commissions', function($table){
			$table->dateTime('paid_on')->nullable();
			$table->integer('paid_by')->unsigned()->nullable();
			$table->foreign('paid_by')->references('id')->on('users');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_commissions', function($table){
			$table->dropColumn('paid_on');
			$table->dropColumn('paid_by');
		});
	}
}
