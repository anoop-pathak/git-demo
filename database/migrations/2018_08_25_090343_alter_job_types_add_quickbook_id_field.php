<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobTypesAddQuickbookIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_types', function(Blueprint $table)
		{
			$table->string('qb_id')->nullable();

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_types', function(Blueprint $table)
		{
			$table->dropColumn('qb_id');

		});
	}

}