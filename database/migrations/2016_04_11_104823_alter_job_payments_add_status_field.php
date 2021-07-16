<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPaymentsAddStatusField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->string('status')->default('unapplied');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->dropColumn('status');
		});
	}

}
