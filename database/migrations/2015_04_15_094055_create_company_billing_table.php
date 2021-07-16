<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyBillingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('company_billing', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->text('card_details');
			$table->integer('company_id');
			$table->string('address');
			$table->string('address_line_1')->nullable();
			$table->string('city');
			$table->integer('state_id');
			$table->integer('country_id');
			$table->string('zip');
			$table->string('email')->nullable();
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
		Schema::drop('company_billing');
	}

}
