<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactEmailTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('contact_email', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('contact_id')->unsigned()->index();
			$table->integer('email_address_id')->unsigned()->index();
			$table->boolean('is_primary')->default(0);
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
		Schema::drop('contact_email');
	}

}
