<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbookStagedEntriesChangeObjectIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbook_staged_entries', function(Blueprint $table)
		{
			DB::statement('ALTER TABLE quickbook_staged_entries MODIFY COLUMN object_id VARCHAR(256) NULL');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbook_staged_entries', function(Blueprint $table)
		{
			DB::statement('ALTER TABLE quickbook_staged_entries MODIFY COLUMN object_id INTEGER NULL');
		});
	}

}
