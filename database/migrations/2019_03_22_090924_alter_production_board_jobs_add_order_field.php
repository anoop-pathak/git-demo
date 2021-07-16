<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterProductionBoardJobsAddOrderField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('production_board_jobs', function(Blueprint $table) {
			$table->integer('order')->default(0)->after('archived');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('production_board_jobs', function(Blueprint $table) {
			$table->dropColumn('order');
		});
	}
}
