<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobCommissionsAddCanceledAtField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_commissions', function(Blueprint $table)
		{
			$table->dateTime('canceled_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_commissions', function(Blueprint $table)
		{
			$table->dropColumn('canceled_at');
		});
	}

}
