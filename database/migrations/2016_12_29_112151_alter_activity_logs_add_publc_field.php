<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterActivityLogsAddPublcField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('activity_logs', function($table) 
		{
			$table->boolean('public')->default(true)
				->comment('If false then only for internal use');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('activity_logs', function($table) 
		{
			$table->dropColumn('public');
		});
	}

}
