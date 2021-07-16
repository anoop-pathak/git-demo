<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddDeletedByColummToProductionBoardColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('production_board_columns', function(Blueprint $table)
		{
			$table->integer('deleted_by')->after('deleted_at');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('production_board_columns', function(Blueprint $table)
		{
			$table->dropColumn('deleted_by');			
		});
	}

}
