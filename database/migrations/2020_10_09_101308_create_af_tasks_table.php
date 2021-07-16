<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfTasksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_tasks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->string('af_id')->nullable()->comment('id');
			$table->string('af_owner_id')->nullable()->comment('OwnerId');
			$table->string('who_id')->index()->nullable()->comment("WhoId");
			$table->string('what_id')->index()->nullable()->comment('WhatId');
			$table->string('task_id')->nullable()->comment('TaskId');
			$table->text('subject')->nullable()->comment('Subject');
			$table->string('status')->nullable()->comment('Status');
			$table->string('priority')->nullable()->comment('Priority');
			$table->text('description')->nullable()->comment('Description');
			$table->text('options')->nullable()->comment('store all data in json format.');
			$table->string('created_by')->index()->nullable()->comment('CreatedById');
			$table->string('updated_by')->index()->nullable()->comment('LastModifiedById');
			$table->string('csv_filename')->nullable()->comment('name of file from which data is imported.');
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
		Schema::drop('af_tasks');
	}

}
