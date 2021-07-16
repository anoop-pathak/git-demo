<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddFileSizeAndFilePathField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table)
		{
			$table->string('file_path')->nullable();
			$table->string('file_size')->nullable();
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
			$table->dropColumn('file_path');
			$table->dropColumn('file_size');
		});
	}

}
