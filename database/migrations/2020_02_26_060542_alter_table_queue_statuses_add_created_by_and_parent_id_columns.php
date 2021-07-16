<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableQueueStatusesAddCreatedByAndParentIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('queue_statuses', function(Blueprint $table) {
			$table->integer('created_by')->nullable()->after('attempts');
			$table->integer('parent_id')->nullable()->after('created_by');
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
			$table->dropColumn('created_by');
			$table->dropColumn('parent_id');
		});
	}

}
