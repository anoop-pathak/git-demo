<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableQbEntityErrors extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qb_entity_errors', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('entity_id')->index();
			$table->string('entity')->nullable();
			$table->string('error_code')->nullable();
			$table->string('error_type')->nullable();
			$table->string('message')->nullable();
			$table->string('details')->nullable();
            $table->text('meta')->nullable();
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
		Schema::dropIfExists('qb_entity_errors');
	}

}
