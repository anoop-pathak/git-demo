<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncCustomersDropParentIdAndMarkedSameColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->dropColumn('parent_id');
			$table->dropColumn('marked_same');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_customers', function(Blueprint $table) {
			$table->integer('parent_id')->nullable();
			$table->boolean('marked_same')->default(false);
		});
	}

}
