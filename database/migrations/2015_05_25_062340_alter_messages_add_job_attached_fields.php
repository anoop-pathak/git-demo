<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMessagesAddJobAttachedFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('messages', function($table){
			$table->integer('customer_id')->nullable();
			$table->integer('job_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('messages', function($table){
			$table->dropColumn('customer_id');
			$table->dropColumn('job_id');
		});
	}
}
