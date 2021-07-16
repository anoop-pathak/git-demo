<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSupplierBranchDivisionAddCompanyIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('supplier_branch_division', function(Blueprint $table) {
			$table->integer('company_id')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('supplier_branch_division', function(Blueprint $table) {
			$table->dropColumn('company_id');
		});
	}

}
