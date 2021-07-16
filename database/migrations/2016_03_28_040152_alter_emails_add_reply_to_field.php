<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailsAddReplyToField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('emails', function($table){
			$table->string('type')->default('sent');
			$table->integer('reply_to')->nullable();
			$table->string('from')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('emails', function($table){
			$table->dropColumn('type');
			$table->dropColumn('reply_to');
			$table->dropColumn('from');
		});
	}

}
