<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->string('user_id')->nullable();
			$table->string('af_id')->index()->comment("id");
			$table->string('username')->nullable()->comment("username");
			$table->string('first_name')->nullable()->comment("firstName");
			$table->string('last_name')->nullable()->comment("lastName");
			$table->string('email')->nullable()->comment("email");
			$table->string('company_name')->nullable()->comment("companyName");
			$table->text('street')->nullable()->comment("Street");
			$table->string('city')->nullable()->comment("City");
			$table->string('state')->nullable()->comment("State");
			$table->string('postal_code')->nullable()->comment("PostalCode");
			$table->string('country')->nullable()->comment("Country");
			$table->string('phone')->nullable()->comment("Phone");
			$table->string('fax')->nullable()->comment("Fax");
			$table->string('mobile_phone')->nullable()->comment("cell");
			$table->string('is_active')->nullable()->comment('status');
			$table->string('about_me')->nullable()->comment('note');
			$table->text('options')->nullable()->comment('all other fields in json.');
			$table->string('csv_filename')->nullable()->comment('name of file from which data is imported.');
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
		Schema::drop('af_users');
	}

}
