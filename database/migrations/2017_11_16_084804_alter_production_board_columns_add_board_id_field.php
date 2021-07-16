<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductionBoardColumnsAddBoardIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('production_board_columns', function(Blueprint $table)
		{
			$table->integer('board_id')->unsigned()->index()->comment('Production board id');
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
			$table->dropColumn('board_id');
		});
	}

}
