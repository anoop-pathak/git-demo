<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentsAddRepeatAndOccurenceFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointments', function(Blueprint $table)
		{
			$table->string('repeat')->nullable();
			$table->string('series_id')->nullable();
			$table->integer('occurence')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointments', function(Blueprint $table)
		{
			$table->dropColumn('repeat');
			$table->dropColumn('occurence');
			$table->dropColumn('series_id');
		});
	}

}
