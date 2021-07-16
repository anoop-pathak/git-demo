<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EmailBounceSummary extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('email_bounce_summary', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('type');
			$table->string('sub_type');
			$table->string('email_address');
			$table->string('status');
			$table->text('reason');
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
		Schema::drop('email_bounce_summary');
	}

}
