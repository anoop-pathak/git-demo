<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSerialNumberAddPrefixField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('serial_numbers', function(Blueprint $table)
		{
			$table->string('prefix')->nullable();
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
			$table->dropColumn('prefix');
		});
	}

}
