<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialMacrosAddTradeIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('macro_details', function(Blueprint $table)
		{
			$table->integer('trade_id')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('macro_details', function(Blueprint $table)
		{
			$table->dropColumn('trade_id');
		});
	}

}
