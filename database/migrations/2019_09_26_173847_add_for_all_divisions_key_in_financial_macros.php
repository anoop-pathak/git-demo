<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForAllDivisionsKeyInFinancialMacros extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_macros', function(Blueprint $table) {
			$table->boolean('all_divisions_access')->default(true);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_macros', function(Blueprint $table) {
			$table->dropColumn('all_divisions_access');
		});
	}

}
