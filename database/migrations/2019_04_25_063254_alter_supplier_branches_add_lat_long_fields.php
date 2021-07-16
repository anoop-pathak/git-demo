<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSupplierBranchesAddLatLongFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('supplier_branches', function(Blueprint $table) {
			$table->float('lat', 10, 6)->after('zip')->nullable();
			$table->float('long', 10, 6)->after('lat')->nullable();
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
			$table->dropColumn('lat');
			$table->dropColumn('long');
		});
	}

}
