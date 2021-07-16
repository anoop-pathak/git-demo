<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialMacrosAddBranchCodeColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_macros', function(Blueprint $table) {
			$table->string('branch_code')->nullable()->after('for_all_trades');
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
			$table->dropColumn('branch_code');
		});
	}

}
