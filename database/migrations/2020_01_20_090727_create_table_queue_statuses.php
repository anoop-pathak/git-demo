<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableQueueStatuses extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('queue_statuses', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id')->nullable()->index();
			$table->string('action');
			$table->string('entity_id')->comment('Id of entity for which queue will be execute');
			$table->string('status');
			$table->integer('attempts')->nullable()->index();
			$table->string('job_queue');
			$table->text('data');
			$table->boolean('has_error')->default(false);
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
		Schema::drop('queue_statuses');
	}

}
