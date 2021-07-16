<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHoverClientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('hover_clients', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('created_by')->index();
			$table->string('access_token');
			$table->string('refresh_token');
			$table->string('owner_type');
			$table->integer('owner_id')->index();
			$table->integer('webhook_id');
			$table->dateTime('expiry_date_time');
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
		Schema::drop('hover_clients');
	}

}
