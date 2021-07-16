<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddQbDesktopFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->string('qb_desktop_id')->nullable();
			$table->boolean('qb_desktop_worksheet')->default(0);
			$table->string('qb_desktop_sequence_number')->nullable();
			$table->string('qb_desktop_txn_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->dropColumn('qb_desktop_id');
			$table->dropColumn('qb_desktop_sequence_number');
			$table->dropColumn('qb_desktop_worksheet');
			$table->dropColumn('qb_desktop_txn_id');
		});
	}

}
