<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHoverReportsAddFileSizeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_reports', function(Blueprint $table)
		{
			$table->integer('file_size')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_reports', function(Blueprint $table)
		{
			$table->dropColumn('file_size');
		});
	}

}
