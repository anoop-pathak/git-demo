<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobFinancialNotes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_financial_notes', function(Blueprint $table) {
	    	$table->increments('id');
	    	$table->integer('company_id')->index();
	    	$table->integer('job_id')->index();
	      	$table->text('note')->nullable();
	      	$table->integer('created_by');
	      	$table->integer('updated_by');
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
		Schema::drop('job_financial_notes');
	}

}
