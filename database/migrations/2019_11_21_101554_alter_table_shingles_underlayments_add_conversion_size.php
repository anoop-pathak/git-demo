<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableShinglesUnderlaymentsAddConversionSize extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('shingles_underlayments', function(Blueprint $table)
		{
			$table->text('conversion_size')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('shingles_underlayments', function(Blueprint $table)
		{
			$table->dropColumn('conversion_size');
		});
	}

}
