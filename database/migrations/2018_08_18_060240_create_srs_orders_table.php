<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSrsOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('srs_orders', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('material_list_id');
			$table->string('order_id')->comment = 'Transaction id';
			$table->string('order_status')->default('order_placed');
			$table->text('order_details');
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
		Schema::drop('srs_orders');
	}

}
