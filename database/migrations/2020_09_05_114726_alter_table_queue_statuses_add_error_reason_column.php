<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQueueStatusesAddErrorReasonColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('queue_statuses', function(Blueprint $table) {
			$table->text('error_reason')->nullable()->after('has_error');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('queue_statuses', function(Blueprint $table) {
			$table->dropColumn('error_reason');
		});
	}

}
