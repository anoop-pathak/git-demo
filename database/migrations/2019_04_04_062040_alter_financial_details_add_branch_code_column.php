<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFinancialDetailsAddBranchCodeColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('financial_details', function(Blueprint $table) {
			$table->string('branch_code')->after('product_code')->nullable();
		});

		Artisan::call('command:srs_financial_details_compy_branch_code');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('financial_details', function(Blueprint $table) {
			$table->dropColumn('branch_code');
		});
	}

}
