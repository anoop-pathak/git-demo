<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateResourcesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('resources', function(Blueprint $table) {
			$table->engine = 'InnoDB';
	    	$table->increments('id');
	    	$table->integer('company_id');
	      	$table->integer('parent_id')->nullable();
	      	$table->integer('lft')->nullable();
	      	$table->integer('rgt')->nullable();
	      	$table->integer('depth')->nullable();
	      	$table->string('name', 255);
	      	$table->double('size');
	      	$table->boolean('thumb_exists');
	      	$table->string('path');
	      	$table->boolean('is_dir');
	      	$table->string('mime_type')->nullable();
	      	$table->boolean('locked');
	      	$table->integer('created_by')->nullable();
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
		Schema::drop('resources');
	}

}
