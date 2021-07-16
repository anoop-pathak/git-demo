<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSupplierBranchesAddCompanyIdAndCompanySupplierIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('supplier_branches', function(Blueprint $table) {
			if (!isIndexExists('supplier_branches', 'supplier_branches_company_id_index')) {
				$table->index('company_id');
			}
			if (!isIndexExists('supplier_branches', 'supplier_branches_company_supplier_id_index')) {
				$table->index('company_supplier_id');
			}
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
			$table->dropIndex('supplier_branches_company_id_index');
			$table->dropIndex('supplier_branches_company_supplier_id_index');
		});
	}

}
