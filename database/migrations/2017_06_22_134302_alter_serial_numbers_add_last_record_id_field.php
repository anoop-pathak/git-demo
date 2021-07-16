<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSerialNumbersAddLastRecordIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('serial_numbers', function(Blueprint $table)
		{
			$table->dropColumn('current');
			$table->integer('last_record_id')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('serial_numbers', function(Blueprint $table)
		{
			$table->integer('current')->default(false);
			$table->dropColumn('last_record_id');
		});
	}

}
