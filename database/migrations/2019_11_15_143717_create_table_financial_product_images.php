<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableFinancialProductImages extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('financial_product_images', function(Blueprint $table) {
	    	$table->increments('id');
	    	$table->integer('company_id')->index();
	    	$table->integer('product_id')->index();
	      	$table->string('name', 255)->nullable();
	      	$table->double('size');
	      	$table->boolean('thumb_exists');
	      	$table->string('path')->nullable();
	      	$table->string('mime_type')->nullable();
	      	$table->softDeletes();
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
		Schema::drop('financial_product_images');
	}

}
