<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialProductsAddBranchCodeAndBranchLogoField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			$table->string('branch_code')->nullable();
			$table->string('branch_logo')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_products', function(Blueprint $table) {
			$table->dropColumn('branch_code');
			$table->dropColumn('branch_logo');
		});
	}

}
