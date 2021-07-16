<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerMetaTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('customer_meta', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('customer_id');
			$table->string('meta_key');
			$table->string('meta_value');
			$table->integer('created_by')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('customer_meta');
	}

}
