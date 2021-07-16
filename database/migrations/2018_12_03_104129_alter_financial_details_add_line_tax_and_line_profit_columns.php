<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialDetailsAddLineTaxAndLineProfitColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function(Blueprint $table) {
			$table->string('line_tax')->nullable();
			$table->string('line_profit')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_details', function(Blueprint $table) {
			$table->dropColumn('line_tax');
			$table->dropColumn('line_profit');
		});
	}

}
