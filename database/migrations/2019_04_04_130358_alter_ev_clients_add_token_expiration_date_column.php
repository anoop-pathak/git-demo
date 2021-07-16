<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEvClientsAddTokenExpirationDateColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('ev_clients', function(Blueprint $table) {
			$table->dateTime('token_expiration_date')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('ev_clients', function(Blueprint $table) {
			$table->dropColumn('token_expiration_date');
		});
	}

}
