<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHoverDeliverableIdToJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('jobs', function(Blueprint $table)
		{
			$table->integer('hover_deliverable_id')->after('hover_user_id')->nullable();
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
			$table->dropColumn('hover_deliverable_id');
		});
	}

}
