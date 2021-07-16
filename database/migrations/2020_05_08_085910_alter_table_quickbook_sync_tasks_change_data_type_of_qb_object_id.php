<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbookSyncTasksChangeDataTypeOfQbObjectId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE quickbook_sync_tasks MODIFY COLUMN qb_object_id bigint null');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE quickbook_sync_tasks MODIFY COLUMN qb_object_id int null');
	}

}
