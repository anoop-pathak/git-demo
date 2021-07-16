<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncTasksAddQbIdAndJpIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table) {
			$table->integer('qb_object_id')->nullable();
			$table->integer('jp_object_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_sync_tasks', function(Blueprint $table) {
			$table->dropColumn('qb_object_id');
			$table->dropColumn('jp_object_id');
		});
	}

}
