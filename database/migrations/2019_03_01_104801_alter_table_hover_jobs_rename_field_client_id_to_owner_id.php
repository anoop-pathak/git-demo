<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHoverJobsRenameFieldClientIdToOwnerId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->dropColumn('client_id');
			$table->integer('owner_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('hover_jobs', function(Blueprint $table)
		{
			$table->dropColumn('owner_id');
			$table->integer('client_id');
		});
	}

}
