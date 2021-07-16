<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobsAddQbDesktopFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs', function(Blueprint $table)
		{
			$table->string('qb_desktop_id')->nullable();
			$table->string('qb_desktop_sequence_number')->nullable();
			$table->boolean('qb_desktop_delete')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('jobs', function(Blueprint $table)
		{
			$table->dropColumn('qb_desktop_id');
			$table->dropColumn('qb_desktop_sequence_number');
			$table->dropColumn('qb_desktop_delete');
		});
	}

}
