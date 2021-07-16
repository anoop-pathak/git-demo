<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobPaymentsAddSerialNumberField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_payments', function(Blueprint $table)
		{
			$table->string('serial_number')->after('id');
		});

		DB::statement("UPDATE job_payments set job_payments.serial_number = job_payments.id");
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
			$table->dropColumn('serial_number');
		});
	}

}
