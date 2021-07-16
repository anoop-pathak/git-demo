<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbookSyncTasksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_sync_tasks', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->string('name');
			$table->string('object');
			$table->integer('object_id');
			$table->text('extra');
			$table->text('payload');
			$table->string('status');
			$table->string('action');
			$table->text('msg');
			$table->tinyInteger('origin')->nullable()->default(0);
			$table->integer('created_by');
			$table->integer('last_modified_by');
			$table->integer('quickbook_webhook_id');
			$table->integer('quickbook_webhook_entry_id');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('quickbook_sync_tasks');
	}

}
