<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEstimationsTableAddEagleviewFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->integer('ev_report_id')->nullable();
			$table->integer('ev_file_type_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->dropColumn('ev_report_id');
			$table->dropColumn('ev_file_type_id');
		});
	}

}
