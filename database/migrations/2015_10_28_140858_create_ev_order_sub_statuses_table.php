<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEvOrderSubStatusesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ev_order_sub_statuses', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->integer('id');
			$table->string('name');
			$table->integer('status_id');
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
		Schema::drop('ev_order_sub_statuses');
	}

}
