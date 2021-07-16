<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQuickbooksQueueAddGroupId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_queue', function(Blueprint $table)
		{
			$table->string('group_id')->nullable();
			$table->string('created_source')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks_queue', function(Blueprint $table)
		{
			$table->dropColumn('group_id');
			$table->dropColumn('created_source');
		});
	}

}
