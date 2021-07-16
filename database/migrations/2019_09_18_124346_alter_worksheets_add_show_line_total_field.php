<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddShowLineTotalField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->boolean('show_line_total')->default(false);
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
			$table->dropColumn('show_line_total');
		});
	}

}
