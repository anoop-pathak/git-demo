<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterCompanySupplierAddSrsBranchDetailField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_supplier', function(Blueprint $table) {
			$table->text('srs_branch_detail')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('company_supplier', function(Blueprint $table) {
			$table->dropColumn('srs_branch_detail');
		});
	}

}
