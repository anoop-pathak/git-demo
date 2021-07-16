<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePhoneMessagesRemoveColJobId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('phone_messages', function(Blueprint $table) {
			// $table->dropColumn('job_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('phone_messages', function(Blueprint $table) {
			// $table->integer('job_id')->after('customer_id')->nullable()->index();
		});
	}

}
