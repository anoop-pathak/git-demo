<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColorCodeColumnToSubscriberStageAttributes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('subscriber_stage_attributes', function(Blueprint $table)
		{
			$table->string('color_code');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('subscriber_stage_attributes', function(Blueprint $table)
		{
			$table->dropColumn('color_code');
		});
	}

}
