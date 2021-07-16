<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderToFinancialMacros extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_macros', function(Blueprint $table)
		{
			$table->integer('order')->nullable()->after('branch_code');
			
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_macros', function(Blueprint $table)
		{
			$table->dropColumn('order');
		});
	}

}
