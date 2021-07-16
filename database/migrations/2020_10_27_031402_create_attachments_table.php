<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttachmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('attachments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('type_id')->index();
			$table->string('type')->index();
			$table->string('ref_type');
			$table->integer('ref_id')->index();
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
		Schema::drop('attachments');
	}

}