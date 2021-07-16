<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbooksAddOnlyOneWaySync extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks', function(Blueprint $table) {
			$table->boolean('only_one_way_sync')->nullable();
		});
		DB::table('quickbooks')->update(['only_one_way_sync'=>true]);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks', function(Blueprint $table) {
			$table->dropColumn('only_one_way_sync');
		});
	}

}
