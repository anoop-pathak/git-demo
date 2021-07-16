<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQbdUnitsOfMeasurementTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbd_units_of_measurement', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id');
			$table->string('name');
			$table->string('type');
			$table->string('base_unit_name');
			$table->string('base_unit_abbreviation');
			$table->string('qb_desktop_id')->nullable();
			$table->string('qb_desktop_sequence_number')->nullable();
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
		Schema::drop('qbd_units_of_measurement');
	}

}
