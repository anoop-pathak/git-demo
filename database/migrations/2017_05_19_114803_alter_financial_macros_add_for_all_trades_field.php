<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialMacrosAddForAllTradesField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('macro_details', function(Blueprint $table)
		{
			$table->boolean('for_all_trades')->default(false);
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
			$table->dropColumn('for_all_trades');
		});
	}

}
