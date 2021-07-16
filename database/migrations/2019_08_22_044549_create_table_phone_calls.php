<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePhoneCalls extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
			Schema::create('phone_calls', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('customer_id')->nullable()->index();
			$table->string('sid')->index();
			$table->string('to_number')->nullable();
			$table->string('from_number')->nullable();
			$table->integer('duration')->nullable();
			$table->integer('call_by')->nullable();
			$table->string('status')->nullable();

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
		Schema::drop('phone_calls');
	}
}
