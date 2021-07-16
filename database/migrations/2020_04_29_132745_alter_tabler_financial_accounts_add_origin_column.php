<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablerFinancialAccountsAddOriginColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_accounts', function(Blueprint $table)
		{
			$table->tinyInteger('origin')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_accounts', function(Blueprint $table)
		{
			$table->dropColumn('origin');
		});
	}

}
