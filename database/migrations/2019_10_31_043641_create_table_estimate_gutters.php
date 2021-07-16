<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableEstimateGutters extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('estimate_gutters', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->string('size')->nullable();
			$table->decimal('amount',16,2)->default(0);
			$table->decimal('protection_amount',16,2)->default(0);
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
		Schema::drop('estimate_gutters');
	}

}
