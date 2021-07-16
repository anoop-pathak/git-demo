<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSusbcriberStages extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('subscriber_stages', function(Blueprint $table) {
	    	$table->increments('id');
	      	$table->integer('subscriber_stage_attribute_id')->index();
	      	$table->integer('company_id')->index();
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
		Schema::drop('subscriber_stages');
	}

}
