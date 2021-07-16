<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentsAddParentIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointments', function(Blueprint $table)
		{
			$table->integer('parent_id')->nullable()->unsigned()->index();
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
			$table->dropColumn('parent_id');
		});
	}

}
