<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterChangeOrderEntitiesAddSupplierIdAndBranchCodeColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('change_order_entities', function(Blueprint $table) {
			$table->integer('supplier_id')->nullable();
			$table->string('branch_code')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('change_order_entities', function(Blueprint $table) {
			$table->dropColumn('supplier_id');
			$table->dropColumn('branch_code');
		});
	}

}
