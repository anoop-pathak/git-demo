<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableClickThruEstimateUsers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('click_thru_estimate_users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('user_id')->index();
			$table->integer('estimate_id')->index();
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
		Schema::drop('click_thru_estimate_users');
	}

}
