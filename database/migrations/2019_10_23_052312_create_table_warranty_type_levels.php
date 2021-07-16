<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableWarrantyTypeLevels extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('warranty_type_levels', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('level_id')->index();
			$table->integer('warranty_id')->index();
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
		Schema::drop('warranty_type_levels');
	}

}
