<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialDetailsAddStylesSizesColorsField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->string('style')->nullable();
			$table->string('size')->nullable();
			$table->string('color')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_details', function(Blueprint $table)
		{
			$table->dropColumn('style');
			$table->dropColumn('size');
			$table->dropColumn('color');
		});
	}

}
