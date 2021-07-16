<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobTypesAddQbAccountIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_types', function(Blueprint $table)
		{
			$table->string('qb_account_id')->nullable();
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
			$table->dropColumn('qb_account_id');
		});
	}

}
