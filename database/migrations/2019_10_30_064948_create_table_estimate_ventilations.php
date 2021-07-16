<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableEstimateVentilations extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('estimate_ventilations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->string('type')->nullable();
			$table->decimal('fixed_amount',16,2)->default(0);
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
		Schema::drop('estimate_ventilations');
	}

}
