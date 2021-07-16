<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMessageThreadParticipantsAddFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('message_thread_participants', function(Blueprint $table) {
			$table->dropColumn('customer_id')->nullable()->index();
			$table->string('ref_type')->nullable()->index();
			$table->integer('ref_id')->nullable()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('message_thread_participants', function(Blueprint $table){
			$table->integer('customer_id')->nullable()->index();
			$table->dropColumn('ref_type')->nullable()->index();
			$table->dropColumn('ref_id')->nullable()->index();
		});
	}

}
