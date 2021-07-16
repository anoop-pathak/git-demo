<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMessageThreadsAddCols extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('message_threads', function(Blueprint $table) {
			$table->string('phone_number')->after('job_id')->nullable();
			$table->string('type')->after('job_id')->default('SYSTEM_MESSAGE')->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('message_threads', function(Blueprint $table){
			$table->dropColumn('phone_number');
			$table->dropColumn('type');
		});
	}

}
