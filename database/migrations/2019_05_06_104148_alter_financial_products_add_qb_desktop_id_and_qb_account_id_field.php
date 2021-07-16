<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialProductsAddQbDesktopIdAndQbAccountIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table)
		{
			$table->string('qb_desktop_id')->nullable();
			$table->string('qb_desktop_account_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_products', function(Blueprint $table)
		{
			$table->dropColumn('qb_desktop_id');
			$table->dropColumn('qb_desktop_account_id');
		});
	}

}
