<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterHoverJobsAddCountryIdAndStateIdTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->integer('state_id')->index();
			$table->integer('country_id')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->dropColumn('state_id');
			$table->dropColumn('country_id');
		});
	}

}
