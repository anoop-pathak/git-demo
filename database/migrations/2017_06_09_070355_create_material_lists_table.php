<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMaterialListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('material_lists', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->string('title');
			$table->integer('job_id');
			$table->integer('worksheet_id');
			$table->integer('serial_number');
			$table->string('file_name')->nullable();
			$table->string('file_path')->nullable();
			$table->string('file_mime_type')->nullable();
			$table->double('file_size')->nullable();
			$table->integer('link_id')->nullable();
			$table->string('link_type')->nullable();
			$table->integer('created_by');
			$table->integer('deleted_by');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('material_lists');
	}

}
