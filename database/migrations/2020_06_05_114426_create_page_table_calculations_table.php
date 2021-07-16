<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePageTableCalculationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('page_table_calculations', function(Blueprint $table) {
			$table->increments('id');
			$table->string('page_type');
			$table->integer('page_id')->index();
			$table->string('ref_id');
			$table->text('head');
			$table->text('body');
			$table->text('foot');
			$table->text('options')->nullable();
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
		Schema::drop('page_table_calculations');
	}

}
