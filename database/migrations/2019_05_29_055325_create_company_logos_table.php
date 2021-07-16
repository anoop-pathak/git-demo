<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyLogosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('company_logos', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('small_logo', 255)->comment('Logo');
			$table->string('large_logo', 255)->comment('Masthead');
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
		Schema::drop('company_logos');
	}

}
