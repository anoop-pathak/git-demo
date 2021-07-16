<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMessageThreadsAddSoftDeletesColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('message_threads', function($table) {
			$table->softDeletes();
			$table->integer('deleted_by')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('message_threads', function($table) {
			$table->dropColumn('deleted_at');
			$table->dropColumn('deleted_by');
		});
	}

}
