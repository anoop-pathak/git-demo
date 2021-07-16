<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AleterNewResourceAddUserIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('new_resources', function(Blueprint $table) {
			$table->integer('user_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('new_resources', function(Blueprint $table) {
			$table->dropColumn('user_id');
		});
	}

}
