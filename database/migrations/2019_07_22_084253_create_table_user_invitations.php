<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUserInvitations extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_invitations', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id')->comment('New company id that has sent invite to user');
			$table->integer('user_id');
			$table->integer('group_id');
			$table->string('status')->default('draft');
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
		Schema::drop('user_invitations');
	}

}
