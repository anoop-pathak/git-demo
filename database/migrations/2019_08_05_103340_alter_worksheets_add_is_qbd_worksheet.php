<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddIsQbdWorksheet extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->boolean('is_qbd_worksheet')->default(false);
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
			$table->dropColumn('is_qbd_worksheet');
		});
	}

}
