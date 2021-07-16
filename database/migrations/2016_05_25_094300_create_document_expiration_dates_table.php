<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentExpirationDatesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('document_expiration_dates', function(Blueprint $table)
		{
			$table->increments('id');
			$table->dateTime('expire_date');
			$table->integer('company_id');
			$table->string('object_type');
			$table->integer('object_id');
			$table->text('description')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('document_expiration_dates');
	}

}
