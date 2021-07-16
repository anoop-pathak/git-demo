<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQboCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbo_customers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('first_name')->nullable();
			$table->string('last_name')->nullable();
			$table->string('company_name')->nullable();
			$table->string('email')->nullable();
			$table->boolean('is_sub_customer')->nullable();
			$table->string('quickbook_id')->nullable();
			$table->string('quickbook_parent_id')->nullable();
			$table->string('display_name');
			$table->string('primary_phone_number')->nullable();
			$table->string('alter_phone_number')->nullable();
			$table->string('mobile_number')->nullable();
			$table->string('jp_mapped_id')->nullable();
			$table->text('meta')->nullable();
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
		Schema::drop('qbo_customers');
	}

}
