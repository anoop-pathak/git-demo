<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorksheetsAddLineTaxAndLineMarginMarkupFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('worksheets', function(Blueprint $table) {
			$table->boolean('line_tax')->default(false);
			$table->boolean('line_margin_markup')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('worksheets', function(Blueprint $table) {
			$table->dropColumn('line_tax');
			$table->dropColumn('line_margin_markup');
		});
	}

}
