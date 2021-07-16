<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateJobCreditsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_credits', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('customer_id');
			$table->integer('job_id');
			$table->float('amount');
			$table->string('method');
			$table->text('note');
			$table->dateTime('date')->nullable();
			$table->string('file_path')->nullable();
			$table->string('echeque_number')->nullable();
			$table->dateTime('canceled')->nullable();
			$table->string('quickbook_id');
			$table->timestamps();
		});
		DB::update("ALTER TABLE job_credits AUTO_INCREMENT = 1000;");
		
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_credits');
	}

}
