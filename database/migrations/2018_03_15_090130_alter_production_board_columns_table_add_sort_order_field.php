<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductionBoardColumnsTableAddSortOrderField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('production_board_columns', function($table)
		{
			$table->integer('sort_order')->default(0);
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
			$table->dropColumn('sort_order');
		});
	}

}
