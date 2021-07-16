<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWorksheetImagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('worksheet_images', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('worksheet_id');
			$table->string('name');
			$table->string('path');
			$table->double('size');
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
		Schema::drop('worksheet_images');
	}

}
