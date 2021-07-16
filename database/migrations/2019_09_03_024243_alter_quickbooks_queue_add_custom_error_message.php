<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbooksQueueAddCustomErrorMessage extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_queue', function(Blueprint $table)
		{
			$table->string('custom_error_msg')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks_queue', function(Blueprint $table)
		{
			$table->dropColumn('custom_error_msg');
		});
	}

}
