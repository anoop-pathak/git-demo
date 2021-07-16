<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSerialNumberAddNewNumberColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('serial_numbers', function(Blueprint $table)
		{
			$table->integer('current_allocated_number');
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
			$table->dropColumn('current_allocated_number');
		});
	}

}
