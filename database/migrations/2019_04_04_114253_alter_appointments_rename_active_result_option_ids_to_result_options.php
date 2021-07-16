<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentsRenameActiveResultOptionIdsToResultOptions extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointments', function(Blueprint $table) {
			$table->renameColumn('active_result_option_ids', 'result_option_ids');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointments', function(Blueprint $table) {
			$table->renameColumn('result_option_ids', 'active_result_option_ids');
		});
	}

}
