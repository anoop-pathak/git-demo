<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('customer_contacts', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('customer_id');
			$table->string('first_name');
			$table->string('last_name');
			$table->string('email')->nullable();
			$table->text('additional_emails')->nullable();
			$table->text('phones')->nullable();
			$table->string('address')->nullable();
			$table->string('address_line_1')->nullable();
			$table->string('city')->nullable();
			$table->integer('state_id')->nullable();
			$table->integer('country_id')->nullable();
			$table->string('zip')->nullable();
			$table->integer('deleted_by')->nullable();
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
		Schema::drop('customer_contacts');
	}

}
