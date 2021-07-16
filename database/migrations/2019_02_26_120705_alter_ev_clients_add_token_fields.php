<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEvClientsAddTokenFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ev_clients', function(Blueprint $table)
		{
			$table->text('access_token')->nullable();
			$table->string('refresh_token')->nullable();
			$table->string('client_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('ev_clients', function(Blueprint $table)
		{
			$table->dropColumn(['access_token', 'refresh_token', 'client_id']);
		});
	}

}
