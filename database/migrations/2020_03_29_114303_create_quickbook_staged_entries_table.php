<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbookStagedEntriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_staged_entries', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('company_id');
			$table->string('object_type');
			$table->integer('object_id');
			$table->string('type')->nullable();
			$table->text('meta')->nullable();
			$table->tinyInteger('status')->nullable();
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
		Schema::drop('quickbook_staged_entries');
	}

}
