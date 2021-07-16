<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableVendorsModifySubContractorIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vendors', function(Blueprint $table) {
			$table->renameColumn('sub_contractor_id', 'ref_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vendors', function(Blueprint $table) {
			$table->renameColumn('ref_id', 'sub_contractor_id');
		});
	}

}
