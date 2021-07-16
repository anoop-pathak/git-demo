<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomTaxesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('custom_taxes', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('title');
			$table->float('tax_rate')->nullable();
			$table->integer('created_by');
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
		Schema::drop('custom_taxes');
	}

}
