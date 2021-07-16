<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductionBoardEntriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('production_board_entries', function(Blueprint $table){
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->foreign('company_id')->references('id')->on('companies');
			$table->integer('job_id')->unsigned();
			$table->foreign('job_id')->references('id')->on('jobs');
			$table->integer('column_id')->unsigned()->comment('production board column id');
			$table->foreign('column_id')->references('id')->on('production_board_columns');
			$table->string('data')->nullable();
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
		Schema::drop('production_board_entries');
	}

}
