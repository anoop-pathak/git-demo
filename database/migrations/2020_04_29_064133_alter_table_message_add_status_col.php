<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMessageAddStatusCol extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('messages', function(Blueprint $table) {
			$table->string('sms_status')->after('content')->nullable();
			$table->string('sms_id')->after('content')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('messages', function(Blueprint $table){
			$table->dropColumn('sms_status');
			$table->dropColumn('sms_id');
		});
	}

}
