<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableClickthruEstimateAddName extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('click_thru_estimates', function(Blueprint $table)
		{
			$table->string('name')->nullable();
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
			$table->dropColumn('name');
		});
	}

}
