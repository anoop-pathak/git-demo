<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JobsTableAddGhostJobConstraint extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs', function(Blueprint $table)
		{
			// DB::statement('ALTER TABLE jobs ADD CONSTRAINT unique_constraint_ghost_job UNIQUE(customer_id, ghost_job)');
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
			// DB::statement('ALTER TABLE `jobs` DROP INDEX `unique_constraint_ghost_job`');
		});
	}

}
