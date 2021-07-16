<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNewResourcesAddAdminOnlyField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('new_resources', function(Blueprint $table)
		{
			$table->boolean('admin_only')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('new_resources', function(Blueprint $table)
		{
			$table->dropColumn('admin_only');
		});
	}

}
