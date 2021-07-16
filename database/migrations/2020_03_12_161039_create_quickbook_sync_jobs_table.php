<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbookSyncJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_sync_jobs', function($table) {
			$table->increments('id');
			$table->integer('company_id');
			$table->boolean('is_project')->default(false);
			$table->boolean('multi_job')->default(false);
			$table->integer('parent_id')->nullable();
			$table->integer('object_id')->nullable();
			$table->text('meta')->nullable();
			$table->string('status', 100)->nullable();
			$table->string('action', 100)->nullable();
			$table->text('msg')->nullable();
			$table->integer('created_by')->nullable();
			$table->integer('last_modified_by')->nullable();
			$table->timestamps();

			$table->index('company_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('quickbook_sync_jobs');
	}

}
