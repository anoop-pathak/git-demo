<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('customers', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id');
			$table->string('first_name');
			$table->string('last_name');
			$table->string('company_name')->nullable();
			$table->string('email')->nullable();
			$table->string('additional_emails')->nullable();
			$table->integer('rep_id');
			$table->integer('address_id');
			$table->integer('billing_address_id');
			$table->integer('referred_by')->nullable();
			$table->string('referred_by_type')->nullable();
			$table->string('referred_by_note')->nullable();
			$table->integer('created_by');
			$table->integer('last_modified_by');
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
		Schema::drop('customers');
	}

}
