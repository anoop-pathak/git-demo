<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMeasurementValuesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('measurement_values', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('measurement_id')->unsigned();
			$table->foreign('measurement_id')->references('id')->on('measurements');
			$table->integer('trade_id')->unsigned();
			$table->foreign('trade_id')->references('id')->on('trades');
			$table->integer('attribute_id')->unsigned();
			$table->foreign('attribute_id')->references('id')->on('measurement_attributes');
			$table->string('value');
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
		Schema::drop('measurement_values');
	}

}
