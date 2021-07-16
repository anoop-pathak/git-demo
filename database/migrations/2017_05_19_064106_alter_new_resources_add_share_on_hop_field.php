<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterNewResourcesAddShareOnHopField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('new_resources', function(Blueprint $table)
		{
			$table->boolean('share_on_hop')->default(false);
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
			$table->dropColumn('share_on_hop');
		});
	}

}
