<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePageTableCalculationsAddTypeIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('page_table_calculations', function(Blueprint $table) {
			$table->integer('type_id')->after('page_type');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('page_table_calculations',function(Blueprint $table){
			$table->dropColumn('type_id');
		});
	}

}
