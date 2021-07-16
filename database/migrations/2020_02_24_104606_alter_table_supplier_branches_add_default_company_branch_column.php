<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableSupplierBranchesAddDefaultCompanyBranchColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('supplier_branches', function(Blueprint $table) {
			$table->boolean('default_company_branch')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('supplier_branches', function(Blueprint $table) {
			$table->dropColumn('default_company_branch');
		});
	}

}
