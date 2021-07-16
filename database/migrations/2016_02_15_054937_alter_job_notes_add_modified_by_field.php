<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobNotesAddModifiedByField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_notes', function(Blueprint $table)
		{
			$table->integer('modified_by');
		});
		DB::statement('UPDATE job_notes SET modified_by = created_by');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_notes', function(Blueprint $table)
		{
			$table->dropColumn('modified_by');
		});
	}

}
