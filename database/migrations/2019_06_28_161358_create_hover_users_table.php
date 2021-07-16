<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class CreateHoverUsersTable extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::create('hover_users', function(Blueprint $table)
 		{
 			$table->increments('id');
 			$table->string('hover_user_id');
 			$table->string('first_name');
 			$table->string('last_name');
 			$table->string('email');
 			$table->string('aasm_state')->nullable();
 			$table->string('acl_template')->nullable();
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
 		Schema::drop('hover_users');
 	}
 }