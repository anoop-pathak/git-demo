<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableEstimateStructures extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('estimate_structures', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->string('name')->nullable();
			$table->string('type')->nullable();
			$table->decimal('amount',16,2)->default(0);
			$table->string('amount_type')->nullable();
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
		Schema::drop('estimate_structures');
	}

}
