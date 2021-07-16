<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('companies', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->string('name');
			$table->string('office_address');
			$table->string('office_address_line_1')->nullable();
			$table->string('office_email');
			$table->text('additional_email')->nullable();
			$table->string('office_city');
			$table->integer('office_state');
			$table->string('office_zip');
			$table->integer('office_country');
			$table->string('office_phone')->nullable();
			$table->text('additional_phone')->nullable();
			$table->string('office_fax')->nullable();
			$table->string('logo')->nullable();
			$table->integer('account_manager_id');
			$table->integer('timezone_id')->nullable();
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
		Schema::drop('companies');
	}

}
