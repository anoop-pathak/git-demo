<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMacroDetailsAddOldProductIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('macro_details', function($table){
			$table->integer('old_product_id')->nullable();
		});

		DB::statement('Update macro_details SET old_product_id=product_id');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('macro_details', function($table){
			$table->dropColumn('old_product_id');
		});
	}

}
