<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailsAddCustomerIdJobIdFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('emails',function($table){
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
		Schema::table('emails',function($table){
			$table->dropColumn('customer_id')->nullable();
			$table->dropColumn('job_id')->nullable();
		});
	}

}
