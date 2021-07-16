<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterClickThruEstimatesAddFilePath extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('click_thru_estimates', function(Blueprint $table)
		{
			$table->text('file')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('click_thru_estimates', function(Blueprint $table)
		{
			$table->dropColumn('file');
		});
	}

}
