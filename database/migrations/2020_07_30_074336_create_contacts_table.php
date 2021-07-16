<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('contacts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->string('type');
			$table->string('company_name')->nullable()->index();
			$table->string('first_name')->nullable()->index();
			//$table->string('middle_name')->nullable();
			$table->string('last_name')->nullable()->index();
			$table->integer('address_id')->nullable()->index();
			$table->integer('created_by');
			$table->integer('last_modified_by');
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
		Schema::drop('contacts');
	}

}
