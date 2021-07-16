<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyDeletedFlags extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('comapny_deleted_flags', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned()->index();
			$table->integer('flag_id')->unsigned()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('comapny_deleted_flags');
	}

}
