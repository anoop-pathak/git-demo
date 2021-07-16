<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDepartmentTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{	
		Schema::dropIfExists('user_role');
		Schema::create('user_department', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('department_id')->unsigned()->index();
			$table->integer('user_id')->unsigned()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('user_department');
	}

}
