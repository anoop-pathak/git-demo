<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMaterialListsAddBranchDetailField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('material_lists', function(Blueprint $table) {
			$table->text('branch_detail')->nullable()->comment('Supplier branch detail.');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('material_lists', function(Blueprint $table) {
			$table->dropColumn('branch_detail');
		});
	}

}
